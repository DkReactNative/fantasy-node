var CronJob = require('cron').CronJob;
var crons = {
    start_jobs: async () => {
        //“At every minute.”
        // var job = new CronJob('*/5 * * * * *', crons_controller.getSeasons, null, true, 'Asia/Kolkata' );
        // job.start();
    }
}
module.exports = crons;