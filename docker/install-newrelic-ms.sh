#!/usr/bin/env bash
set -euo pipefail

NR_TGZ="$(curl -fsSL https://download.newrelic.com/php_agent/release/ \
    | grep -oE 'newrelic-php5-[0-9A-Za-z._+-]+-linux\.tar\.gz' \
    | grep -vF -- '-linux-musl' \
    | sort -uV | tail -n1)"
if [[ -z "${NR_TGZ}" ]]; then
    echo 'install-newrelic-ms.sh: could not resolve New Relic glibc tarball from release index' >&2
    exit 1
fi

echo "New Relic tarball: ${NR_TGZ}"

CACHE_ROOT="/var/cache/newrelic-agent"
CACHE_FILE="${CACHE_ROOT}/${NR_TGZ}"
mkdir -p "${CACHE_ROOT}"

if [[ ! -s "${CACHE_FILE}" ]]; then
    curl -fsSL "https://download.newrelic.com/php_agent/release/${NR_TGZ}" -o "${CACHE_FILE}.part"
    mv "${CACHE_FILE}.part" "${CACHE_FILE}"
fi

cp "${CACHE_FILE}" /tmp/nr.tgz
tar -xzf /tmp/nr.tgz -C /tmp
export NR_INSTALL_USE_CP_NOT_LN=1 NR_INSTALL_SILENT=1
/tmp/newrelic-php5-*/newrelic-install install
rm -rf /tmp/nr.tgz /tmp/newrelic-php5-* /tmp/nrinstall*

sed -i \
    -e "s/\"REPLACE_WITH_REAL_KEY\"/\"${NEW_RELIC_LICENSE_KEY:-}\"/" \
    -e "s/newrelic.appname = \"PHP Application\"/newrelic.appname = \"${NEW_RELIC_APP_NAME:-}\"/" \
    -e 's/;newrelic.daemon.app_connect_timeout =.*/newrelic.daemon.app_connect_timeout=15s/' \
    -e 's/;newrelic.daemon.start_timeout =.*/newrelic.daemon.start_timeout=5s/' \
    -e 's|^;\?newrelic\.daemon\.logfile = .*|newrelic.daemon.logfile = "/var/log/php/newrelic-daemon.log"|' \
    -e 's|^;\?newrelic\.logfile = .*|newrelic.logfile = "/var/log/php/newrelic-php_agent.log"|' \
    /usr/local/etc/php/conf.d/newrelic.ini

touch /var/log/php/newrelic-daemon.log /var/log/php/newrelic-php_agent.log
chmod 666 /var/log/php/newrelic-daemon.log /var/log/php/newrelic-php_agent.log
