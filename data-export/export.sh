#!/usr/bin/env bash
set -o errexit -o errtrace -o noclobber -o nounset -o pipefail
IFS=$'\n\t'

script_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)

function usage() {
    if [ -n "${1:-}" ]; then
    >&2 cat <<EOF
$1

EOF
    fi

    >&2 cat <<EOF
Usage: ${BASH_SOURCE[0]} project-dir site-uri

EOF
    exit 1
}

if (( $# < 2 )); then
    usage "Too few arguments"
fi

project_dir="$1"
uri="$2"

if [ -z "$project_dir" ]; then
    usage "Invalid project directory"
fi

if [ ! -d "$project_dir" ] ; then
    (>&2 echo 'Project directory "'"$project_dir"'" does not exist')
    exit 1
fi

if [ -z "$uri" ]; then
    usage "Invalid site-uri"
fi

cd "$project_dir"

filenames=("$script_dir"/export_*.sql)

for filename in "${filenames[@]}"; do
    echo "$filename"

    # JSON

    # https://tldp.org/LDP/abs/html/string-manipulation.html
    output_filename=${filename/%.sql/.json}
    # https://github.com/drush-ops/drush/issues/3071#issuecomment-347929777
    vendor/bin/drush --uri="$uri" php:eval "return \Drupal::database()->query(file_get_contents('$filename'))->fetchAll()" --format=json >| "$output_filename" || true
    echo "$output_filename"

    # CSV

    output_filename=${filename/%.sql/.csv}
    # https://stackoverflow.com/a/22421445/2502647
    vendor/bin/drush --uri="$uri" sql:cli < "$filename" | awk 'BEGIN { FS="\t"; OFS="," } {
  rebuilt=0
  for(i=1; i<=NF; ++i) {
    if ($i ~ /,/ && $i !~ /^".*"$/) {
      gsub("\"", "\"\"", $i)
      $i = "\"" $i "\""
      rebuilt=1
    }
  }
  if (!rebuilt) { $1=$1 }
  print
}' >| "$output_filename" || true
    echo "$output_filename"
done
