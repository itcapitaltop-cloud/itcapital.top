ARG BASE_IMAGE=ubuntu:22.04
FROM ${BASE_IMAGE}
USER root

ARG WWWGROUP=1000
ARG WWWUSER=1000
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg curl ca-certificates cron \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.4-cli php8.4-pgsql php8.4-sqlite3 php8.4-gd \
    php8.4-curl php8.4-mbstring php8.4-xml php8.4-zip php8.4-bcmath \
    php8.4-intl php8.4-readline php8.4-redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create symlink for php command
RUN ln -s /usr/bin/php8.4 /usr/local/bin/php

# Create the sail user/group so scheduled tasks run as the same uid/gid (1000)
# as php-fpm in the app container — keeps storage/logs ownership consistent
RUN groupadd --force -g $WWWGROUP sail \
    && useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u $WWWUSER sail

# Set working directory
WORKDIR /var/www/html

# Copy crontab and install it for the sail user (cron daemon runs jobs as sail)
COPY crontab /etc/cron.d/app
RUN chmod 0644 /etc/cron.d/app && \
    chown root:root /etc/cron.d/app && \
    crontab -u sail /etc/cron.d/app

COPY --chmod=755 entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
