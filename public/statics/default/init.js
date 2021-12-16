function dynamicLoadCss(url) {
    var head = document.getElementsByTagName('head')[0];
    var link = document.createElement('link');
    link.type='text/css';
    link.rel = 'stylesheet';
    link.href = url;
    head.appendChild(link);
}
function dynamicLoadJs(url, callback) {
    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;
    if(typeof(callback)=='function'){
        script.onload = script.onreadystatechange = function () {
            if (!this.readyState || this.readyState === "loaded" || this.readyState === "complete"){
                callback();
                script.onload = script.onreadystatechange = null;
            }
        };
    }
    head.appendChild(script);
}

dynamicLoadCss(sys_theme+'global/plugins/font-awesome/css/font-awesome.min.css');
dynamicLoadCss(sys_theme+'global/plugins/simple-line-icons/simple-line-icons.min.css');
dynamicLoadCss(sys_theme+'global/plugins/uniform/css/uniform.default.css');
dynamicLoadCss(sys_theme+'global/css/plugins.min.css');

dynamicLoadCss(sys_theme+'pages/css/search.min.css');
dynamicLoadCss(sys_theme+'apps/css/todo-2.min.css');
dynamicLoadCss(home_theme_path+'layouts/layout3/css/themes/default.min.css');
dynamicLoadCss(home_theme_path+'layouts/layout3/css/custom.min.css');



dynamicLoadJs(sys_theme+'global/plugins/bootstrap/js/bootstrap.min.js');
dynamicLoadJs(sys_theme+'global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js');
dynamicLoadJs(sys_theme+'global/plugins/jquery-slimscroll/jquery.slimscroll.min.js');
dynamicLoadJs(sys_theme+'global/plugins/bootstrap-switch/js/bootstrap-switch.min.js');
dynamicLoadJs(sys_theme+'global/plugins/jquery.blockui.min.js');
dynamicLoadJs(sys_theme+'global/plugins/uniform/jquery.uniform.min.js');
dynamicLoadJs(sys_theme+'global/scripts/app.min.js');

dynamicLoadJs(home_theme_path+'layouts/layout3/scripts/layout.min.js');
dynamicLoadJs(home_theme_path+'layouts/layout3/scripts/demo.min.js');
dynamicLoadJs(home_theme_path+'layouts/global/scripts/quick-sidebar.min.js');

dynamicLoadJs(theme_path+'js/dialog-plus.js');
dynamicLoadJs(theme_path+'js/jquery.artDialog.js?skin=default');

dynamicLoadJs(theme_path+'js/dayrui.js');
dynamicLoadCss(theme_path+'js/ui-dialog.css');