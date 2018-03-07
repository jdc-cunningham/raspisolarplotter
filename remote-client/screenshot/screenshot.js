// today's date
var utc = new Date().toJSON().slice(0,10);  

// check if image exists
var request = new XMLHttpRequest();  
request.open('GET', 'http://raspisolarplotter.com/screenshot/'+utc+'.jpeg', true);
request.onreadystatechange = function(){
    if (request.readyState === 4){
        if (request.status === 404) {  
            // continue with request
            var page = require('webpage').create();
            page.viewportSize = {
              width: 1366,
              height: 768
            };
            page.open('http://raspisolarplotter.com/', function() {  
              window.setTimeout(function() {
                page.render(utc+'.jpeg', {format: 'jpeg', quality: '100'});
                phantom.exit();
              }, 5000); // 5 seconds should be plenty
            });
        }  
        else {
          // exit phantomjs no need to run
          phantom.exit();
        }
    }
};
request.send();
