function ycmcBatchVerify(obj, ...params) {
    var ids = $("#grid").yiiGridView("getSelectedRows");
    if (ids.length === 0) {
        rfError("错误", "没有选中任何项");return;
    }
    var url = $(obj).attr("href");
    $.ajax({
        type: "post",
        url: url,
        dataType: "json",
        data: {ids: ids, params:params},
        success: function (data) {
            var type = 'success';
            if (parseInt(data.code) !== 200) {
                type = 'error';
            }
            swal({
                title: data.message,
                text: rfText(),
                type: type,
                icon: type,
                button: "确定",
            }).then((value) => {
                location.reload();
            })
        }
    });
}