# OS2Loop AI stuff

Clone the [`os2loop-ai-stuff` branch](https://github.com/itk-dev/os2loop/tree/ai-stuff):

``` shell
git clone --branch ai-stuff https://github.com/itk-dev/os2loop os2loop-ai-stuff
```

[The scripts](#scripts) must be run in a running [OS2Loop setup](https://github.com/itk-dev/os2loop) by adding the
`os2loop-ai-stuff` directory (with the Git clone from above) as a volume to the `phpfpm` container, e.g.

``` shell
docker compose run --rm --volumes $PWD/os2loop-ai-stuff:/os2loop-ai-stuff phpfpm /os2loop-ai-stuff/data-export/export.sh
```

> [!NOTE]
> Change `$PWD/os2loop-ai-stuff` to match your actual setup.

## Scripts

``` shell
data-export/export.sh
```

## Development

``` shell
task
```
