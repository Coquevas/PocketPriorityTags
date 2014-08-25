<?php

namespace Pocket;

use cli\progress\Bar;
use Curl\Curl;
use Duellsy\Pockpack\Pockpack;
use Duellsy\Pockpack\PockpackQueue;
use Pocket;
use SocialCounters\SocialCounters;
use URLResolver;

class PriorityTags
{
    private $curl;
    private $debug = false;
    private $dryRun = false;
    private $pocket;
    private $resolver;

    const DEBUG_MAX_ITEMS = 5;
    const TAG_MIN_RATING = 100;

    /**
     * @param Curl $curl
     * @param Pockpack $pocket
     * @param URLResolver $resolver
     * @param bool $debug Verbose mode
     * @param bool $dryRun Dry run (no changes)
     */
    public function __construct(Curl $curl, Pockpack $pocket, URLResolver $resolver, $debug, $dryRun)
    {
        $this->curl = $curl;
        $this->debug = $debug;
        $this->dryRun = $dryRun;
        $this->pocket = $pocket;
        $this->resolver = $resolver;

        $this->initResolver();
    }

    public function run()
    {
        $readingList = $this->retrieveReadingList();
        $ratedList = $this->computeSocialRating($readingList);
        $ratedList = $this->sortByRating($ratedList);
        $this->updatePriorityTags($ratedList);
    }

    private function getTagsByPartialName($haystack, $needle)
    {
        $foundTags = array();

        foreach ($haystack as $tagItem) {
            $pos = strpos($tagItem->tag, $needle);
            if ($pos !== false) {
                $foundTags[] = $tagItem->tag;
            }
        }

        return $foundTags;
    }

    private function toReadableNumber($n, $precision = 0)
    {
        if ($n < 1000) {
            // Anything less than a thousand
            $formattedNumber = number_format(floor($n / self::TAG_MIN_RATING) * self::TAG_MIN_RATING, $precision);
        } elseif ($n < 1000000) {
            // Anything less than a million
            $formattedNumber = number_format($n / 1000, $precision) . 'K';
        } elseif ($n < 1000000000) {
            // Anything less than a billion
            $formattedNumber = number_format($n / 1000000, $precision) . 'M';
        } else {
            // At least a billion
            $formattedNumber = number_format($n / 1000000000, $precision) . 'B';
        }

        return $formattedNumber;
    }

    private function initResolver()
    {
        // Identify your crawler (otherwise the default will be used)
        $this->resolver->setUserAgent('Mozilla/5.0 (compatible; PocketPriorityTags/1.0; +http://getpocket.com/)');

        // Designate a temporary file that will store cookies during the session.
        // Some web sites test the browser for cookie support, so this enhances results.
        $this->resolver->setCookieJar('/tmp/url_resolver.cookies', true);
    }

    /**
     * @param array $ratedList
     * @return array
     */
    private function sortByRating(Array $ratedList)
    {
        usort($ratedList, array($this, 'compareItems'));
        return $ratedList;
    }

    /**
     * @param array $a
     * @param array $b
     * @return int Must be less than, equal to, or greater than zero if the first argument is considered to be
     * respectively less than, equal to, or greater than the second.
     */
    private function compareItems($a, $b)
    {
        if ($a['rating'] > $b['rating']) {
            return -1;
        } else {
            if ($a['rating'] < $b['rating']) {
                return 1;
            }
        }
        return 0;
    }

    private function retrieveReadingList()
    {
        $progressBar = new Bar('Downloading reading list', self::TAG_MIN_RATING);
        $progressBar->tick();
        // TODO: Change pockpak to use the CURLOPT_NOPROGRESS support (and pull request)
        // TODO: Local cache and request only changes since the last OK timestamp
        $options = array('detailType' => 'complete');
        if ($this->debug) {
            $options['count'] = self::DEBUG_MAX_ITEMS;
        }
        $apiResponse = $this->pocket->retrieve($options);
        $progressBar->finish();

        if ($this->debug) {
            var_dump($apiResponse->list);
        }

        return $apiResponse->list;
    }

    /**
     * @param $readingList
     * @return array
     */
    private function computeSocialRating($readingList)
    {
        $progressBar = new Bar('Getting social relevance', count(get_object_vars($readingList)));
        $ratedList = array();

        foreach ($readingList as $savedLink) {
            if ($this->debug) {
                var_dump($savedLink);
            }

            $progressBar->tick();

            $pocketResolvedUrl = isset($savedLink->resolved_url) ? : $savedLink->given_url;
            $givenUrl = $savedLink->given_url;

            //TODO: Review. Too much value for different URLs
            if ($givenUrl != '') {
                $myResolvedUrl = $this->resolver->resolveURL($givenUrl)->getURL();
                if ($givenUrl == $pocketResolvedUrl && $givenUrl == $myResolvedUrl) {
                    $rating = SocialCounters::getRelevance($givenUrl, $this->debug);
                } elseif ($givenUrl == $pocketResolvedUrl) {
                    $rating = SocialCounters::getRelevance($givenUrl, $this->debug)
                        + SocialCounters::getRelevance($myResolvedUrl, $this->debug);
                } elseif ($givenUrl == $myResolvedUrl) {
                    $rating = SocialCounters::getRelevance($givenUrl, $this->debug)
                        + SocialCounters::getRelevance($pocketResolvedUrl, $this->debug);
                } else {
                    $rating = SocialCounters::getRelevance($givenUrl, $this->debug)
                        + SocialCounters::getRelevance($pocketResolvedUrl, $this->debug)
                        + SocialCounters::getRelevance($myResolvedUrl, $this->debug);
                }
            } else {
                $rating = 0;
            }

            $ratedList[] = array(
                'rating' => $rating,
                'item' => $savedLink,
            );
        }
        $progressBar->finish();
        return $ratedList;
    }

    /**
     * @param $ratedList
     */
    private function updatePriorityTags($ratedList)
    {
        if ($this->debug) {
            var_dump($ratedList);
        }

        $actions = new PockpackQueue();
        $ratedListSize = count($ratedList);
        $progressBar = new Bar(' Updating priority tags', $ratedListSize);

        for ($i = 0; $i < $ratedListSize; $i++) {
            $progressBar->tick();
            $item = $ratedList[$i]['item'];
            $rating = $ratedList[$i]['rating'];
            if (isset($item->tags) && count($item->tags) > 0) {
                $tagsForDeletion = array_unique(
                    array_merge(
                        $this->getTagsByPartialName($item->tags, 'zz-score-'),
                        $this->getTagsByPartialName($item->tags, 'zz-'),
                        array('001', '010', '040')
                    )
                );

                $actions->tags_remove(
                    array(
                        'item_id' => $item->item_id,
                        'tags' => $tagsForDeletion,
                    )
                );
            }

            $tags = array();
            if ($i < 1) {
                $tags = array('040', '010', '001');
            } elseif ($i < 10) {
                $tags = array('040', '010');
            } elseif ($i < 40) {
                $tags = array('040');
            }

            if ($rating >= self::TAG_MIN_RATING) {
                $tags[] = 'zz-' . $this->toReadableNumber($rating);
            }

            if (!empty($tags)) {
                $actions->tags_add(
                    array(
                        'item_id' => $item->item_id,
                        'tags' => $tags,
                    )
                );
            }

            $actionCount = count($actions->getActions());
            $shallSendActions = $actionCount >= 10 || (($i == $ratedListSize - 1) && ($actionCount > 0));
            if ($shallSendActions) {
                $apiResponse = '';

                if (!$this->dryRun) {
                    $apiResponse = $this->pocket->send($actions);
                }

                if ($this->debug) {
                    var_export($actions->getActions());
                    var_export($apiResponse);
                }
                $actions->clear();
            }
        }
        $progressBar->finish();
    }
}