
var my_host = document.domain;
var my_domain = my_host.split('.');
if (my_domain.length === 3) {
    my_host = my_domain[1] + '.' + my_domain[2];
}
my_host = 'ws.'+ my_host;
var WebIM = {};
WebIM.config = {
    wsUrl: (location.protocol === 'https:' ? 'wss:' : 'ws:') + my_host + ":8443",
    // wsUrl: "wss://www.vgvtycbguimeng.cc:8443",
};
