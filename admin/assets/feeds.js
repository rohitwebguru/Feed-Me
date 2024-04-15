jQuery(document).ready(function(){
    jQuery('#feedme_feed_poll_frequency').length
    var feeds=(jQuery('#feedme_feed_poll_frequency').length);
   // const currentDate = new Date();
    //const timestamp = currentDate.getTime();
    //var c_time = new currentDate.getTime();
    //console.log(timestamp); 
    if (feeds > 0) {
        var hjds = jQuery('#feedme_feed_poll_frequency').val();
        hjds = hjds/60;
         Date.prototype.addHours = function(h) {
            this.setTime(this.getTime() +

            (h * 60 * 60 * 1000));

            return this;

        }
         var a = new Date();
         a.addHours(hjds);
         var feedme_1 = a.getDate();
         var feedme_2 = a.getMonth()+1;
         var feedme_3 = a.getFullYear();
         var feedme_4 = a.getHours()
         var feedme_5 = a.getMinutes();

        var feedme_feeds =  feedme_3+'-'+ feedme_2+'-'+feedme_1 +' '+feedme_4+':'+feedme_5;
        const currentDate = a.getTime();
        const timestamp = Math.floor(currentDate/1000);
        feedme_feeds = timestamp;
        jQuery('#feedme_feed_nextpoll').val(feedme_feeds);
    }

    jQuery('#feedme_feed_poll_frequency').change(function(){
        var feeds=(jQuery('#feedme_feed_poll_frequency').length);
        
        if (feeds > 0) {
            var hjds = jQuery('#feedme_feed_poll_frequency').val();
            hjds = hjds/60;

            Date.prototype.addHours = function(h) {
                this.setTime(this.getTime() + (h * 60 * 60 * 1000));
                return this;
            }

            var a = new Date();
            a.addHours(hjds);
            var feeds_1 = a.getDate();
            var feeds_2 = a.getMonth()+1;
            var feeds_3 = a.getFullYear();
            var feeds_4 = a.getHours();
            var feeds_5 = a.getMinutes();

            var feeds_1 = a.getDate();
            var feeds_feedme =  feeds_3+'-'+ feeds_2+'-'+ feeds_1+' '+feeds_4+':'+feeds_5;
            const currentDate = a.getTime();
            const timestamp = Math.floor(currentDate/1000);
            feedme_feeds = timestamp;
            jQuery('#feedme_feed_nextpoll').val(timestamp);
        }   
    });
})