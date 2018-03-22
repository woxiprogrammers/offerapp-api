Prerequisites:

php version >= 7.1
composer

Project Installation steps:

1.Clone this repository in a folder using command :
    git clone <repository url/ssh key>

2.Switch to branch 'develop' Run command:
    git checkout develop

3. Install Composer         
    composer install
Above command will create 'vendor' folder in your project directory. 

4.Now give 777 permission to following folders.
    storage/
 
5.You need to setup virtual hosts also.

6. If memcached not install ->
    sudo apt-get install php7.1-memcached