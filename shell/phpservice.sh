#! /bin/sh
#
# chkconfig: - 55 45
# description:  The phpservice daemon is a php background service.
# processname: phpservice
# config: /etc/sysconfig/phpservice
# pidfile: /var/run/phpservice/phpservice.pid

# Standard LSB functions
#. /lib/lsb/init-functions

# Source function library.
. /etc/init.d/functions

USER=root
OPTIONS=""

if [ -f /etc/sysconfig/phpservice ];then 
        . /etc/sysconfig/phpservice
fi

# Check that networking is up.
. /etc/sysconfig/network

if [ "$NETWORKING" = "no" ]
then
        exit 0
fi

RETVAL=0
prog="phpservice"
pidfile=${PIDFILE-/var/run/phpservice/phpservice.pid}
lockfile=${LOCKFILE-/var/lock/subsys/phpservice}

start () {
        echo -n $"Starting $prog: "
        [ -d /var/run/phpservice ] || mkdir -m 666 -p /var/run/phpservice
        # Ensure that /var/run/phpservice has proper permissions
        if [ "`stat -c %U /var/run/phpservice`" != "$USER" ]; then
                chown $USER /var/run/phpservice
        fi

        /usr/bin/php -f /path/to/phpservice.php $OPTIONS 2>&1 >>/var/log/${prog}.log &
        ps -ef|grep "phpservice\.php"|grep -v grep|awk '{print $2}' > ${pidfile}
        RETVAL=$?
        echo -e '                                         [  \033[0;32;1mOK\033[0m  ]'
        [ $RETVAL -eq 0 ] && touch ${lockfile}
}
stop () {
        echo -n $"Stopping $prog: "
        killproc -p ${pidfile} ${prog}
        RETVAL=$?
        echo
        if [ $RETVAL -eq 0 ] ; then
                rm -f ${lockfile} ${pidfile}
        fi
}

restart () {
        stop
        start
}


# See how we were called.
case "$1" in
  start)
        if [ -f ${lockfile} ];then 
            echo "$prog is running..."
        else
            start
        fi
        ;;
  stop)
        stop
        ;;
  status)
        status -p ${pidfile} ${prog}
        RETVAL=$?
        ;;
  restart|reload|force-reload)
        restart
        ;;
  condrestart|try-restart)
        [ -f ${lockfile} ] && restart || :
        ;;
  *)
        echo $"Usage: $0 {start|stop|status|restart|reload|force-reload|condrestart|try-restart}"
        RETVAL=2
        ;;
esac

exit $RETVAL