#! /bin/sh
#
# chkconfig: - 55 45
# description:  The shservice daemon is a shell service.
# processname: shservice
# config: /etc/sysconfig/shservice
# pidfile: /var/run/shservice/shservice.pid

# Standard LSB functions
#. /lib/lsb/init-functions

# Source function library.
. /etc/init.d/functions

PORT=22201
USER=root
DBDIR=/var/lib/shservice/data
MAXCONN=8192
SIZE=5120
OPTIONS=""

if [ -f /etc/sysconfig/shservice ];then 
        . /etc/sysconfig/shservice
fi

# Check that networking is up.
. /etc/sysconfig/network

if [ "$NETWORKING" = "no" ]
then
        exit 0
fi

RETVAL=0
prog="shservice"
pidfile=${PIDFILE-/var/run/shservice/shservice.pid}
lockfile=${LOCKFILE-/var/lock/subsys/shservice}

start () {
        echo -n $"Starting $prog: "
	[ -d /var/run/shservice ] || mkdir -p /var/run/shservice
        # Ensure that /var/run/shservice has proper permissions
        if [ "`stat -c %U /var/run/shservice`" != "$USER" ]; then
                chown $USER /var/run/shservice
        fi

	daemon --pidfile ${pidfile} /usr/local/bin/shservice -d -p $PORT -u root -P ${pidfile} -N -H $DBDIR -B $SIZE -A $MAXCONN $OPTIONS
        RETVAL=$?
        echo
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
        start
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