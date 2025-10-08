ARG BASE_IMAGE

FROM ${BASE_IMAGE}

USER root

RUN apt-get update && apt-get install -y cron && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY crontab /etc/cron.d/app

RUN chmod 0644 /etc/cron.d/app && chown root:root /etc/cron.d/app

COPY --chmod=755 entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
