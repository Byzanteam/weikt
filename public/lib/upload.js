var accessid = ''
var accesskey = ''
var host = ''
var policyBase64 = ''
var signature = ''
var callbackbody = ''
var filename = ''
var key = ''
var expire = 0
var g_object_name = ''
var g_object_name_type = ''
var now = timestamp = Date.parse(new Date()) / 1000;


function send_request() {
    var xmlhttp = null;
    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    if (xmlhttp != null) {
        // serverUrl是 用户获取 '签名和Policy' 等信息的应用服务器的URL，请将下面的IP和Port配置为您自己的真实信息
        serverUrl = '/console/Common/getOssSignature'

        xmlhttp.open("GET", serverUrl, false);
        xmlhttp.send(null);
        return xmlhttp.responseText
    } else {
        alert("Your browser does not support XMLHTTP.");
    }
};

function check_object_radio() {
    var tt = document.getElementsByName('myradio');
    for (var i = 0; i < tt.length; i++) {
        if (tt[i].checked) {
            g_object_name_type = tt[i].value;
            break;
        }
    }
}

function get_signature() {
    // 可以判断当前expire是否超过了当前时间， 如果超过了当前时间， 就重新取一下，3s 作为缓冲。
    now = timestamp = Date.parse(new Date()) / 1000;
    if (expire < now + 3) {
        body = send_request()
        var obj = eval("(" + body + ")");
        host = obj['host']
        policyBase64 = obj['policy']
        accessid = obj['accessid']
        signature = obj['signature']
        expire = parseInt(obj['expire'])
        callbackbody = obj['callback']
        key = obj['dir']
        return true;
    }
    return false;
};

function random_string(len) {　　
    len = len || 32;　　
    var chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';　　
    var maxPos = chars.length;　　
    var pwd = '';　　
    for (i = 0; i < len; i++) {　　
        pwd += chars.charAt(Math.floor(Math.random() * maxPos));
    }
    return pwd;
}

function get_suffix(filename) {
    pos = filename.lastIndexOf('.')
    suffix = ''
    if (pos != -1) {
        suffix = filename.substring(pos)
    }
    return suffix;
}

function calculate_object_name(filename) {
    if (g_object_name_type == 'local_name') {
        g_object_name += "${filename}"
    } else if (g_object_name_type == 'random_name') {
        suffix = get_suffix(filename)
        g_object_name = key + random_string(12) + suffix
    }
    return ''
}

function get_uploaded_object_name(filename) {
    if (g_object_name_type == 'local_name') {
        tmp_name = g_object_name
        tmp_name = tmp_name.replace("${filename}", filename);
        return tmp_name
    } else if (g_object_name_type == 'random_name') {
        return g_object_name
    }
}

function set_upload_param(up, filename, ret) {
    if (ret == false) {
        ret = get_signature()
    }

    g_object_name = key;
    if (filename != '') {
        suffix = get_suffix(filename)
        calculate_object_name(filename)
    }
    new_multipart_params = {
        'key': g_object_name,
        'policy': policyBase64,
        'OSSAccessKeyId': accessid,
        'success_action_status': '200', //让服务端返回200,不然，默认会返回204
        'callback': callbackbody,
        'signature': signature,
    };

    up.setOption({
        'url': host,
        'multipart_params': new_multipart_params
    });

    up.start();
}

var uploader = new plupload.Uploader({
    runtimes: 'html5,flash,silverlight,html4',
    browse_button: 'selectfiles',
    multi_selection: false,
    container: document.getElementById('container'),
    flash_swf_url: 'lib/plupload-2.1.2/js/Moxie.swf',
    silverlight_xap_url: 'lib/plupload-2.1.2/js/Moxie.xap',
    url: 'http://oss.aliyuncs.com',

    filters: {
        mime_types: [ //只允许上传图片和zip文件
            { title: "Image files", extensions: "jpg,gif,png,bmp" }
        ],
        max_file_size: '10mb', //最大只能上传10mb的文件
        prevent_duplicates: true //不允许选取重复文件
    },

    init: {
        PostInit: function() {
            if (document.getElementById('ossfile') && document.getElementById('postfiles')) {
                if (window.location.href.indexOf('edit') < 0) {
                    document.getElementById('ossfile').innerHTML = '等待上传';
                }
                document.getElementById('postfiles').onclick = function() {
                    set_upload_param(uploader, '', false);
                    return false;
                };
            }

        },

        FilesAdded: function(up, files) {
            document.getElementById('ossfile').innerHTML = "";
            plupload.each(files, function(file) {
                document.getElementById('ossfile').innerHTML += '<div id="' + file.id + '">' + file.name + '<b> 0%</b>' +
                    '</div>';
            });
        },

        BeforeUpload: function(up, file) {
            g_object_name_type = 'random_name'
            check_object_radio();
            set_upload_param(up, file.name, true);
        },

        UploadProgress: function(up, file) {
            var d = document.getElementById(file.id);
            if (d) {
                d.getElementsByTagName('b')[0].innerHTML = '<span> ' + file.percent + "%</span>";
            }
        },

        FileUploaded: function(up, file, info) {
            if (info.status == 200 || info.status == 203) {
                if (document.getElementById(file.id)) {
                    document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '上传成功';
                }
                document.getElementById('ossfile').setAttribute('data-url', host + g_object_name);
            }
            // else if (info.status == 203)
            // {
            //     document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '上传到OSS成功，但是oss访问用户设置的上传回调服务器失败，失败原因是:' + info.response;
            // }
            else {
                document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '上传失败';
            }
        },

        Error: function(up, err) {
            if (err.code == -600) {
                document.getElementById('ossfile').innerHTML = '选择的文件过大';
            } else if (err.code == -601) {
                document.getElementById('ossfile').innerHTML = '文件格式不对';
            } else if (err.code == -602) {
                document.getElementById('ossfile').innerHTML = '该文件已经上传了';
            } else {
                document.getElementById('ossfile').innerHTML = '上传失败';
            }
        }
    }
});

uploader.init();