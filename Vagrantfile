# -*- mode: ruby -*-
# vi: set ft=ruby :

# windows friendly development
use_smb = false

Vagrant.configure(2) do |config|
  config.vm.box = "debian/jessie64"

  config.vm.network "forwarded_port", guest: 80, host: 8080
  config.vm.network "forwarded_port", guest: 443, host: 8443

  if use_smb
    config.vm.synced_folder ".", "/vagrant", type: :smb
  else
    config.vm.synced_folder ".", "/vagrant", type: :nfs
  end

  config.vm.provision "shell", inline: <<-SHELL
    debconf-set-selections <<< 'mysql-server mysql-server/root_password password root'
    debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password root'

    sudo apt-get update
    sudo apt-get install -y wget git
    sudo apt-get install -y mysql-server mysql-client
    sudo apt-get install -y apache2 php5 php5-mysql php5-gd php5-curl libapache2-mod-php5

    sudo chown -R vagrant:vagrant /var/www
    rm -rf /var/www/*
    cd /var/www
    wget https://de.wordpress.org/latest-de_DE.zip
    unzip latest-de_DE.zip
    rm latest-de_DE.zip

    echo '<VirtualHost *:80>
            DocumentRoot /var/www/wordpress

            LogLevel warn

            ErrorLog /vagrant/log/error.log
            
            <Directory /var/www/wordpress>
              Options +Indexes +FollowSymlinks
              AllowOverride all
              Allow from all
              Order allow,deny
              Require all granted
            </Directory>
    </VirtualHost>

    <VirtualHost *:443>
            DocumentRoot /var/www/wordpress

            LogLevel warn

            ErrorLog /vagrant/log/error.log
            
            SSLEngine on
            SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
            SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

            <Directory /var/www/wordpress>
              Options +Indexes +FollowSymlinks
              AllowOverride all
              Allow from all
              Order allow,deny
              Require all granted
            </Directory>
    </VirtualHost>' > /etc/apache2/sites-enabled/000-default.conf

    mkdir /vagrant/log

    sed -i -e 's/export APACHE_RUN_USER=www-data/export APACHE_RUN_USER=vagrant/' /etc/apache2/envvars
    sed -i -e 's/export APACHE_RUN_GROUP=www-data/export APACHE_RUN_GROUP=vagrant/' /etc/apache2/envvars

    chown -R vagrant:vagrant /var/www/wordpress

    sudo make-ssl-cert generate-default-snakeoil --force-overwrite

    sudo a2enmod ssl
    sudo a2enmod rewrite
    sudo service apache2 restart

    mysql -u root -proot -e "create database wordpress"

    ln -s /vagrant /var/www/wordpress/wp-content/plugins/podlove-podcasting-plugin-for-wordpress

    cd /vagrant
    curl -sS https://getcomposer.org/installer | php
    php composer.phar --dev install
  SHELL
end