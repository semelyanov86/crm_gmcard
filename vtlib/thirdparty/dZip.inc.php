<?php
/**
 * DOWNLOADED FROM: http://www.phpclasses.org/browse/package/2495/
 * License: 	BSD License.
 */
?>
<?php
class dZip
{
    public $filename;

    public $overwrite;

    public $zipSignature = "\x50\x4b\x03\x04"; // local file header signature

    public $dirSignature = "\x50\x4b\x01\x02"; // central dir header signature

    public $dirSignatureE = "\x50\x4b\x05\x06"; // end of central dir signature

    public $files_count  = 0;

    public $fh;

    public function __construct($filename, $overwrite = true)
    {
        $this->filename  = $filename;
        $this->overwrite = $overwrite;
    }

    public function addDir($dirname, $fileComments = '')
    {
        if (substr($dirname, -1) != '/') {
            $dirname .= '/';
        }
        $this->addFile(false, $dirname, $fileComments);
    }

    public function ensureFh()
    {
        if (!$this->fh) {
            $this->fh = fopen($this->filename, $this->overwrite ? 'wb' : 'a+b');
        }
    }

    public function addFile($filename, $cfilename, $fileComments = '', $data = false)
    {
        $this->ensureFh();

        // $filename can be a local file OR the data wich will be compressed
        if (substr($cfilename, -1) == '/') {
            $details['uncsize'] = 0;
            $data = '';
        } elseif (file_exists($filename)) {
            $details['uncsize'] = filesize($filename);
            $data = file_get_contents($filename);
        } elseif ($filename) {
            echo "<b>Cannot add {$filename}. File not found</b><br>";

            return false;
        } else {
            $details['uncsize'] = strlen($data); // Prasad: Fixed instead of strlen($filename)
            // DATA is given.. use it! :|
        }

        // if data to compress is too small, just store it
        if ($details['uncsize'] < 256) {
            $details['comsize'] = $details['uncsize'];
            $details['vneeded'] = 10;
            $details['cmethod'] = 0;
            $zdata = $data;
        } else { // otherwise, compress it
            $zdata = gzcompress($data);
            $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug (thanks to Eric Mueller)
            $details['comsize'] = strlen($zdata);
            $details['vneeded'] = 10;
            $details['cmethod'] = 8;
        }

        $details['bitflag'] = 0;
        $details['crc_32']  = crc32($data);

        // Convert date and time to DOS Format, and set then
        $lastmod_timeS  = str_pad(decbin(date('s') >= 32 ? date('s') - 32 : date('s')), 5, '0', STR_PAD_LEFT);
        $lastmod_timeM  = str_pad(decbin(date('i')), 6, '0', STR_PAD_LEFT);
        $lastmod_timeH  = str_pad(decbin(date('H')), 5, '0', STR_PAD_LEFT);
        $lastmod_dateD  = str_pad(decbin(date('d')), 5, '0', STR_PAD_LEFT);
        $lastmod_dateM  = str_pad(decbin(date('m')), 4, '0', STR_PAD_LEFT);
        $lastmod_dateY  = str_pad(decbin(date('Y') - 1_980), 7, '0', STR_PAD_LEFT);

        // echo "ModTime: $lastmod_timeS-$lastmod_timeM-$lastmod_timeH (".date("s H H").")\n";
        // echo "ModDate: $lastmod_dateD-$lastmod_dateM-$lastmod_dateY (".date("d m Y").")\n";
        $details['modtime'] = bindec("{$lastmod_timeH}{$lastmod_timeM}{$lastmod_timeS}");
        $details['moddate'] = bindec("{$lastmod_dateY}{$lastmod_dateM}{$lastmod_dateD}");

        $details['offset'] = ftell($this->fh);
        fwrite($this->fh, $this->zipSignature);
        fwrite($this->fh, pack('s', $details['vneeded'])); // version_needed
        fwrite($this->fh, pack('s', $details['bitflag'])); // general_bit_flag
        fwrite($this->fh, pack('s', $details['cmethod'])); // compression_method
        fwrite($this->fh, pack('s', $details['modtime'])); // lastmod_time
        fwrite($this->fh, pack('s', $details['moddate'])); // lastmod_date
        fwrite($this->fh, pack('V', $details['crc_32']));  // crc-32
        fwrite($this->fh, pack('I', $details['comsize'])); // compressed_size
        fwrite($this->fh, pack('I', $details['uncsize'])); // uncompressed_size
        fwrite($this->fh, pack('s', strlen($cfilename)));   // file_name_length
        fwrite($this->fh, pack('s', 0));  // extra_field_length
        fwrite($this->fh, $cfilename);    // file_name
        // ignoring extra_field
        fwrite($this->fh, $zdata);

        // Append it to central dir
        $details['external_attributes']  = (substr($cfilename, -1) == '/' && !$zdata) ? 16 : 32; // Directory or file name
        $details['comments']             = $fileComments;
        $this->appendCentralDir($cfilename, $details);
        ++$this->files_count;
    }

    public function setExtra($filename, $property, $value)
    {
        $this->centraldirs[$filename][$property] = $value;
    }

    public function save($zipComments = '')
    {
        $this->ensureFh();

        $cdrec = '';
        foreach ($this->centraldirs as $filename => $cd) {
            $cdrec .= $this->dirSignature;
            $cdrec .= "\x0\x0";                  // version made by
            $cdrec .= pack('v', $cd['vneeded']); // version needed to extract
            $cdrec .= "\x0\x0";                  // general bit flag
            $cdrec .= pack('v', $cd['cmethod']); // compression method
            $cdrec .= pack('v', $cd['modtime']); // lastmod time
            $cdrec .= pack('v', $cd['moddate']); // lastmod date
            $cdrec .= pack('V', $cd['crc_32']);  // crc32
            $cdrec .= pack('V', $cd['comsize']); // compressed filesize
            $cdrec .= pack('V', $cd['uncsize']); // uncompressed filesize
            $cdrec .= pack('v', strlen($filename)); // file comment length
            $cdrec .= pack('v', 0);                // extra field length
            $cdrec .= pack('v', strlen($cd['comments'])); // file comment length
            $cdrec .= pack('v', 0); // disk number start
            $cdrec .= pack('v', 0); // internal file attributes
            $cdrec .= pack('V', $cd['external_attributes']); // internal file attributes
            $cdrec .= pack('V', $cd['offset']); // relative offset of local header
            $cdrec .= $filename;
            $cdrec .= $cd['comments'];
        }
        $before_cd = ftell($this->fh);
        fwrite($this->fh, $cdrec);

        // end of central dir
        fwrite($this->fh, $this->dirSignatureE);
        fwrite($this->fh, pack('v', 0)); // number of this disk
        fwrite($this->fh, pack('v', 0)); // number of the disk with the start of the central directory
        fwrite($this->fh, pack('v', $this->files_count)); // total # of entries "on this disk"
        fwrite($this->fh, pack('v', $this->files_count)); // total # of entries overall
        fwrite($this->fh, pack('V', strlen($cdrec)));     // size of central dir
        fwrite($this->fh, pack('V', $before_cd));         // offset to start of central dir
        fwrite($this->fh, pack('v', strlen($zipComments))); // .zip file comment length
        fwrite($this->fh, $zipComments);

        fclose($this->fh);
    }

    // Private
    public function appendCentralDir($filename, $properties)
    {
        $this->centraldirs[$filename] = $properties;
    }
}
?>
