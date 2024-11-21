#!/usr/bin/env sh

script_dir=$(cd "$(dirname "$0")" && pwd)

usage() {
    if [ -n "${1:-}" ]; then
    >&2 cat <<EOF
$1

EOF
    fi

    >&2 cat <<EOF
Usage: $0 site-uri

EOF
    exit 1
}

site_uri="$1"

if [ -z "$site_uri" ]; then
    usage "Missing site-uri"
fi

# cd "$project_dir"

filenames="$script_dir/export_*.sql"

for filename in $filenames; do
    echo "Running $filename"

    # JSON

    # https://tldp.org/LDP/abs/html/string-manipulation.html
    output_filename="$filename.json"
    # https://github.com/drush-ops/drush/issues/3071#issuecomment-347929777
    vendor/bin/drush --uri="$site_uri" php:eval "return \Drupal::database()->query(file_get_contents('$filename'))->fetchAll()" --format=json >| "$output_filename" || true
    ls -lh "$output_filename"

    # CSV

    output_filename="$filename.csv"
    # https://stackoverflow.com/a/22421445/2502647
    vendor/bin/drush --uri="$site_uri" sql:cli < "$filename" | awk 'BEGIN { FS="\t"; OFS="," } {
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
    ls -lh "$output_filename"

done
