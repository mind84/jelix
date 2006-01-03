;<?php die(''); ?>
;for security reasons , don't remove or modify the first line

defaultModule = "{$appname}"
defaultAction = "default"
defaultLocale = "fr_FR"
defaultCharset = "ISO-8859-1"

checkTrustedModules = off

; list of modules : module,module,module
trustedModules =

pluginsPath = lib:jelix-plugins/,app:plugins/
modulesPath = lib:jelix-modules/,app:modules/
tplpluginsPath = lib:jelix/tpl_plugins/

dbProfils = dbprofils.ini.php


useTheme = false
defaultTheme = default

[plugins]
;nom = nom_fichier_ini

[responses]


[error_handling]
messageLogFormat = "%date%\t[%code%]\t%msg%\t%file%\t%line%\n"
logFile = error.log
email = root@localhost
emailHeaders = "From: webmaster@yoursite.com\nX-Mailer: Jelix\nX-Priority: 1 (Highest)\n"

; mots cl�s que vous pouvez utiliser : ECHO, EXIT, LOGFILE, SYSLOG, MAIL
default      = ECHO EXIT
error        = ECHO EXIT
warning      = ECHO
notice       =
strict       =
; pour les exceptions, il y a implicitement un EXIT
exception    = ECHO



[compilation]
check_cache_filetime  = on
force  = off

[urlengine]

;indique si vous utiliser IIS comme serveur
use_IIS = off

;indique le param�tre dans $_GET ou est indiqu� le path_info
IIS_path_key = __JELIX_URL__

;indique si il faut stripslash� le path_info r�cup�r� par le biais de url_IIS_path_key
IIS_stripslashes_path_key = on

default_entrypoint= index
entrypoint_extension= .php

engine        = default
enable_parser = on
multiview_on = off
notfound_dest = "jelix~notfound"

[urlengine_specific_entrypoints]
;/foo/index.php = "mymodule"
;foo.php = "*~myaction"

