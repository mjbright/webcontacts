# Docker demo image, as used on try.jupyter.org and tmpnb.org

FROM ubuntu:latest

MAINTAINER Michael Bright  <dockerfile@mjbright.net>

# Perform initial update/upgrade to get latest packages/security updates:
RUN apt-get update && \
    apt-get upgrade -y

# Install mysql++:
RUN apt-get install -y mysql pdo apache2
    apt-get install -y texlive-latex-extra

ADD find.php index.php jquery.min.js .
ADD phpinfo2.php phpinfo.php .

