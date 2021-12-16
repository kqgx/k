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
dynamicLoadCss(sys_theme+'css/font-awesome/css/font-awesome.css');
dynamicLoadCss(sys_theme+'css/table_form.css');
dynamicLoadCss(sys_theme+'global/plugins/simple-line-icons/simple-line-icons.min.css');
dynamicLoadCss(sys_theme+'global/plugins/uniform/css/uniform.default.css');
dynamicLoadCss(sys_theme+'global/plugins/bootstrap-switch/css/bootstrap-switch.min.css');
dynamicLoadCss(sys_theme+'global/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css');
dynamicLoadCss(sys_theme+'global/plugins/bootstrap-colorpicker/css/colorpicker.css');
dynamicLoadCss(sys_theme+'global/plugins/jquery-minicolors/jquery.minicolors.css');
dynamicLoadCss(sys_theme+'global/css/plugins.min.css');
dynamicLoadCss(sys_theme+'my.css');
dynamicLoadCss(sys_theme+'css/index.css');

dynamicLoadJs(sys_theme+'global/plugins/bootstrap/js/bootstrap.min.js');
dynamicLoadJs(sys_theme+'global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js');
dynamicLoadJs(sys_theme+'global/plugins/jquery-slimscroll/jquery.slimscroll.min.js');
dynamicLoadJs(sys_theme+'global/plugins/bootstrap-switch/js/bootstrap-switch.min.js');
dynamicLoadJs(sys_theme+'global/plugins/jquery.blockui.min.js');
dynamicLoadJs(sys_theme+'global/plugins/uniform/jquery.uniform.min.js');
dynamicLoadJs(sys_theme+'global/plugins/bootstrap-tagsinput/bootstrap-tagsinput.min.js');
dynamicLoadJs(sys_theme+'global/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.js');
dynamicLoadJs(sys_theme+'global/plugins/jquery-minicolors/jquery.minicolors.min.js');
dynamicLoadJs(sys_theme+'global/plugins/bootstrap-confirmation/bootstrap-confirmation.min.js');
dynamicLoadJs(sys_theme+'global/scripts/app.min.js');
dynamicLoadJs(sys_theme+'layouts/layout/scripts/layout.min.js');
dynamicLoadJs(sys_theme+'layouts/layout/scripts/demo.min.js');
dynamicLoadJs(sys_theme+'layouts/global/scripts/quick-sidebar.min.js');
dynamicLoadJs(sys_theme+'tree/tree.js');

dynamicLoadJs(theme_path+'js/jquery-ui.min.js');
dynamicLoadJs(theme_path+'js/jquery.cookie.js');
dynamicLoadJs(theme_path+'js/dialog-plus.js');
dynamicLoadJs(theme_path+'js/jquery.artDialog.js?skin=default');
dynamicLoadJs(theme_path+'js/validate.js');
dynamicLoadJs(theme_path+'js/admin.js');
dynamicLoadJs(theme_path+'js/dayrui.js');
dynamicLoadCss(theme_path+'js/ui-dialog.css');