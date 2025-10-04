ARG BASE_IMAGE

FROM ${BASE_IMAGE}

USER root

RUN apk update && apk add --no-cache supervisor

COPY supervisord.conf /etc/supervisord.conf
COPY app.ini /etc/supervisor.d/app.ini
COPY --chmod=755 entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
