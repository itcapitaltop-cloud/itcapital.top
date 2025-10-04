FROM php:8.4.11-fpm-alpine3.21

ARG APP_ENV="local"
ARG COMPOSER_INSTALL_DEV="false"

COPY --chown=1000:1000 . .

RUN if [ "$APP_ENV" != "local" ]; then \
    if [ "$COMPOSER_INSTALL_DEV" = "true" ]; then \
    composer install --no-scripts --no-progress --prefer-dist; \
    else \
    composer install --no-scripts --no-progress --prefer-dist --no-dev; \
    fi; \
    composer dump-autoload --optimize && \
    composer clear-cache; \
    else \
    echo "Skipping composer install in local environment"; \
    fi

COPY --chmod=755 entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
