#! /bin/sh

#set config
USER=root
PASSWD=root


#yum install epel-release

yum install -y redis
yum install -y mysql-server

export HOME=/home/work
mkdir -p $HOME
chmod a+w $HOME
export WORKSPACE=$HOME/open-falcon
mkdir -p $WORKSPACE
cd $WORKSPACE

git clone https://github.com/open-falcon/scripts.git
cd ./scripts/


mysql -h localhost -u $USER --password="$PASSWD" < db_schema/graph-db-schema.sql
mysql -h localhost -u $USER --password="$PASSWD" < db_schema/dashboard-db-schema.sql
mysql -h localhost -u $USER --password="$PASSWD" < db_schema/portal-db-schema.sql
mysql -h localhost -u $USER --password="$PASSWD" < db_schema/links-db-schema.sql
mysql -h localhost -u $USER --password="$PASSWD" < db_schema/uic-db-schema.sql


DOWNLOAD="https://github.com/open-falcon/of-release/releases/download/v0.1.0/open-falcon-v0.1.0.tar.gz"
cd $WORKSPACE

mkdir ./tmp
#下载
wget $DOWNLOAD -O open-falcon-latest.tar.gz

#解压
tar -zxf open-falcon-latest.tar.gz -C ./tmp/
for x in `find ./tmp/ -name "*.tar.gz"`;do \
    app=`echo $x|cut -d '-' -f2`; \
    mkdir -p $app; \
    tar -zxf $x -C $app; \
done


yum install python-setuptools
easy_install virtualenv


cd ~
#wget http://dinp.qiniudn.com/go1.4.1.linux-amd64.tar.gz
wget https://storage.googleapis.com/golang/go1.4.1.linux-amd64.tar.gz

tar zxf go1.4.1.linux-amd64.tar.gz
mkdir -p workspace/src
echo "" >> .bashrc
echo 'export GOROOT=$HOME/go' >> .bashrc
echo 'export GOPATH=$HOME/workspace' >> .bashrc
echo 'export PATH=$GOROOT/bin:$GOPATH/bin:$PATH' >> .bashrc
echo "" >> .bashrc
source .bashrc

cd $GOPATH/src
mkdir github.com
cd github.com
#git clone --recursive https://github.com/open-falcon/of-release.git

wget https://github.com/open-falcon/of-release/archive/master.zip
unzip master.zip
mv of-release-master open-falcon
