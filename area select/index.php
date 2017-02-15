<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>FeeIDC</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta name="author" content="FeeIDC" />
    <meta name="keywords" content="FeeIDC, APP" />
    <meta name="description" content="FeeIDC, APP" />
    <script src="js/jquery-1.7.1.min.js"></script>
    <script src="js/city.js"></script>
    <style>
        #region{width:600px;}
        #region div select{float:left; width:160px;}
    </style>
</head>
<body>
<div id="region">
    <div><select id="province" name="province"></select></div>
    <div><select id="city" name="city"></select></div>
    <div><select id="area" name="area"></select></div>
</div>
<script>
$(document).ready(function(){
    function doChange(region, subRegion){
        var pcode = region == 0? 1: $(region).val();
        var str   = region == 0? "<option value='0'></option>": "";
        $.each(FEEIDC_citys, function(code, city){
            if(city[1] == pcode){
               str = str + "<option value='"+ code +"'>" + city[0] + "</option>";
            }
        });
        $(subRegion).html(str);
        region == "#province" && doChange("#city", "#area");
    }
    doChange(0, "#province");
    $("#province").bind("change", function(){doChange("#province", "#city");});
    $("#city").bind("change", function(){doChange("#city", "#area");});
});
</script>
</body>
</html>