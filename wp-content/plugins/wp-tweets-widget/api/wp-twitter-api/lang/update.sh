#!/bin/bash
#
# Script pulls down latest translations from Loco and splits into two text domains via tag
#

APIKEY=""

cd "`dirname $0`"

function loco {
    echo "Pulling $1_$2..."
    if [ "enGB" = "$1$2" ]; then
        wget --quiet --header="Authorization: Loco $APIKEY" "https://localise.biz/api/export/locale/en.pot?filter=twitter-api" -O "twitter-api.pot"
        wget --quiet --header="Authorization: Loco $APIKEY" "https://localise.biz/api/export/locale/en.pot?filter=error-codes" -O "extra/twitter-errors.pot"
    else
        wget --quiet --header="Authorization: Loco $APIKEY" "https://localise.biz/api/export/locale/$1-$2.po?filter=twitter-api" -O "twitter-api-$1_$2.po"
        wget --quiet --header="Authorization: Loco $APIKEY" "https://localise.biz/api/export/locale/$1-$2.po?filter=error-codes" -O "extra/twitter-errors-$1_$2.po"
        msgfmt --no-hash "twitter-api-$1_$2.po" -o "twitter-api-$1_$2.mo"
        msgfmt --no-hash "extra/twitter-errors-$1_$2.po" -o "extra/twitter-errors-$1_$2.mo"
    fi
}

loco en GB
loco es ES
loco de DE
loco nl NL
loco pt BR
loco ru RU

echo Done.
