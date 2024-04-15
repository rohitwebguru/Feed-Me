<?php
class FeedItemDispatcher{
    private $feedme_scheduler = 'feedme_testing';
    public function __construct( $backgroundAsync) {
        $this->feedme_scheduler = $backgroundAsync;
    }

    /**
     * @descripiton  Add Feed Item ID To Job Queue
     * @param   Id   This will be the feed item id
     * @return  Null
     */
    public function add_feedItem_to_queue($feed_item_id = '' ){
        $this->feedme_scheduler->push_to_queue( $feed_item_id );
        $this->feedme_scheduler->save()->dispatch();
    }
}