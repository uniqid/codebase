<?php 
/*
	1.设置要清除的文件夹路径  $dir 

	2.把要清除的文件夹只读属性去掉
*/
$dir = "E:/PHPnow/htdocs/hmbst/";

del_svndir($dir,1);

function del_svndir($dir,$loop="0")
{
	if (is_dir($dir))
	{
		$dir = str_replace("\\","/",$dir);
		if ("/" != substr($dir,-1))
		{
			$dir.= "/";
		}
		$fp  = @opendir($dir);
		while ($fp && $file = @readdir($fp))
		{
			if ($file == '.svn' && is_dir($dir.$file))
			{
				echo $dir.$file,"<br>";
				del_allfiles($dir.$file);
				rmdir($dir.$file);
			}
			elseif($file!='.' && $file!='..' && is_dir($dir.$file))
			{
				del_svndir($dir.$file,$loop);
			}
		}
		closedir($fp);
	}
	else
	{
		echo "Path Error.";
	}
}

function del_allfiles($dir)
{
	if (is_dir($dir))
	{
		$dir = str_replace("\\","/",$dir);
		if ("/" != substr($dir,-1))
		{
			$dir.= "/";
		}
		$fp  = @opendir($dir);
		while ($fp && $file = @readdir($fp))
		{
			if ($file!='.' && $file!='..' && $file !='.svn' && !is_dir($dir.$file))
			{
				unlink($dir.$file);
			}
			elseif($file!='.' && $file!='..' && is_dir($dir.$file))
			{
				del_allfiles($dir.$file);
				rmdir($dir.$file);
			}
		}
		closedir($fp);
	}
}
?>
