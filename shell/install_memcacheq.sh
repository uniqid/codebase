#!/bin/sh
# Reference：
##      http://memcachedb.org/memcacheq/INSTALL.html
cd
mkdir -p libs
cd libs

echo "##### BerkeleyDB #####"
wget http://download.oracle.com/berkeley-db/db-6.1.26.tar.gz
tar xvzf db-6.1.26.tar.gz 
cd db-6.1.26/build_unix/
../dist/configure
make
make install
cd ../..

echo '/usr/local/BerkeleyDB.6.1/lib' > /etc/ld.so.conf.d/libdb.conf
ldconfig


echo "##### libevent #####"
wget http://sourceforge.net/projects/levent/files/libevent/libevent-2.0/libevent-2.0.22-stable.tar.gz
tar xvzf libevent-2.0.22-stable.tar.gz 
cd libevent-2.0.10-stable/
./configure
make
make install
cd ..


echo "##### MemcacheQ #####"
wget https://github.com/uniqid/libs/archive/master.zip
unzip master.zip
tar xzvf libs-master/memcacheq-0.2.0.tar.gz 
cd memcacheq-0.2.0/
./configure --enable-threads --with-bdb=/usr/local/BerkeleyDB.6.1 --with-libevent=/usr/local/lib
make
make install
cd ..

ln -s /usr/local/lib/libevent-2.0.so.5 /usr/lib64/


###### run memcacheq services #########
mkdir -p /var/lib/memcacheq
#/usr/local/bin/memcacheq -d -u root -P /var/run/memcacheq.pid -N -H /var/lib/memcacheq/data -B 5120 -A 8192

