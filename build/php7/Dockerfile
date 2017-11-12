FROM php:7.1-fpm

RUN echo "deb http://www.deb-multimedia.org jessie main non-free" >> /etc/apt/sources.list && \
    echo "deb-src http://www.deb-multimedia.org jessie main non-free" >> /etc/apt/sources.list && \
    apt-get update -y && \
    apt-get install deb-multimedia-keyring -y --force-yes && \
    apt-get update -y && \
    apt-get install build-essential libmp3lame-dev libvorbis-dev libtheora-dev libspeex-dev yasm pkg-config libfaac-dev libopenjpeg-dev libx264-dev -y && \
    apt-get install wget git -y && \
    docker-php-ext-install opcache && \
    docker-php-ext-install mysqli && \
    docker-php-ext-enable opcache && \
    docker-php-ext-enable mysqli && \
    mkdir /tmp/soft && \
    cd /tmp/soft && \
    wget http://ffmpeg.org/releases/ffmpeg-3.4.tar.bz2 && \
    cd .. && \
    mkdir src && \
    cd src && \
    tar xvjf ../soft/ffmpeg-3.4.tar.bz2 && \
    cd ffmpeg-3.4 && \
    ./configure --enable-gpl --enable-postproc --enable-swscale --enable-avfilter --enable-libmp3lame \
	--enable-libvorbis --enable-libtheora --enable-libx264 --enable-libspeex --enable-shared \
	--enable-pthreads --enable-libopenjpeg --enable-nonfree && \
    make && \
    make install && \
    /sbin/ldconfig