#!/bin/bash
#
# Script merges all PO files with latest POT and builds MOs
#



cd "`dirname $0`"

function merge {
    echo "Merging $1_$2..."
    msgmerge  "twitter-api-$1_$2.po" "twitter-api.pot" --update && \
    msgfmt --no-hash "twitter-api-$1_$2.po" -o "twitter-api-$1_$2.mo"
}

merge es ES
merge de DE
merge nl NL
merge pt BR
merge ru RU

echo Done.
