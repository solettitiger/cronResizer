# cronResizer

You load your images from the camera to your website, but they are to big to be used for the website. Run cronResizer to reduce the size to IMG_MAX_WIDTH/IMG_MAX_HEIGHT. The original file is been saved in the subdirectory `./orig/` along with the reduced and optionally cropped image.
It can take a long time to run cronResizer, depending on the size and number of files to be converted. The output from cronResizer shows the files that are being converted. Run cronResizer several times until no more files are listed.


## Requirements
- Webserver with at least PHP 5.6 is needed. GD library is used to convert images.
- Privilege to write to all the images folders for cronResizer to store the resized images.


## Installation
- Put cronResizer onto your webserver and edit the section # Configuration #.
- Edit your `/etc/crontab` file to run cronResizer regulary. E.g. to run the script every hour you would write:
`*/60 * * * * /usr/bin/php /{the path to your website}/cronResizer.php`


## License
Licensed under the [MIT License](https://opensource.org/licenses/MIT). For the software, this one relies on, please refer to the relevant regulations there.

