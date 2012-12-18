<?php
/**
 * THIS GENERATES SOUNDSDFSDFSDFSDFSDFSDFSDF
 */
define('MAX_TIME', 10);

class WaveGenerator {

    private $chunkSize = null;
    private $chunk2size = null;
    private $dataSize = null;
    private $bps = null;
    private $btyerate = null;
    private $channels = null;
    private $samplerate = null;
    private $blockAlign = null;

    public function generate($length, $channels = 1, $bps = 16, $samplerate = 44100) {
        $this->channels = $channels;
        $this->bps = $bps; // bits per second
        $this->samplerate = $samplerate;

        // ByteRate == SampleRate * NumChannels * BitsPerSample/8
        $this->byterate = $this->samplerate * $this->channels * ($bps / 8);
        $this->blockAlign = $channels * $bps / 8;

        // Determine size
        $size = $this->byterate * $length;

        $this->chunkSize = 36 + $size;
        $this->chunk2Size = $size;

        $filename = "wav-" . md5(time()) . ".wav";
        $handle = fopen($filename, "wb");

        // WAV header
        fwrite($handle, "RIFF");
        fwrite($handle, pack("V", $this->chunkSize));

        fwrite($handle, "WAVE");
        fwrite($handle, "fmt ");
        fwrite($handle, pack("V", 0x00000010)); // 16byte size of header for PCM
        fwrite($handle, pack("v", 0x01)); // 1 for PCM
        fwrite($handle, pack("v", $this->channels));
        fwrite($handle, pack("V", $this->samplerate));
        fwrite($handle, pack("V", $this->byterate));
        fwrite($handle, pack("v", $this->blockAlign));
        fwrite($handle, pack("v", $this->bps));

        fwrite($handle, "data");
        fwrite($handle, pack("V", $this->chunk2Size));

        $pos = 0;
        while ($pos < $this->chunk2Size) {
            if ($this->chunk2Size - $pos >= 1024) {
                // Write in big chunks.
                $data = null;
                for($x=0;$x<64;$x++){
                    // 1024 Bytes. LLLL = 16, 16 * 64 = 1024
                    $data .= pack("LLLL", mt_rand() % mt_getrandmax(), mt_rand() % mt_getrandmax(), mt_rand() % mt_getrandmax(), mt_rand() % mt_getrandmax());
                }
                fwrite($handle, $data);
                $pos += 1024;
            } else {
                fwrite($handle, pack("C", mt_rand() % 65536));
                $pos++;
            }
        }

        fclose($handle);

        return $filename;
    }

}
$output = '';
if (isset($_GET['duration'])) {
    $duration = abs(intval($_GET['duration']));
    if ($duration <= 0) {
        return;
    }
    elseif ($duration > MAX_TIME) {
        print "<span style='color: red;'>Stop trying to crash my computer. 10 seconds max.</span>";
        $duration = 1;
    }
    $dorp = new WaveGenerator();
    $file = $dorp->generate($duration);

    $output = "<a href=\"$file\">Here's yo file</a>";
}
?>

<html>
    <body>
        <h1>WAVE Noise Generator</h1>
        <form action="" method="GET">
            <label for="time">Enter wav time. <?php print MAX_TIME; ?>s max.</label>
            <input type="text" name="duration" id="time"/>
            <input type="submit"/>
        </form>
        <?php print $output; ?>
        <hr/>
<pre>
Offset  Size  Name             Description
<hr noshade>
The canonical WAVE format starts with the RIFF header:

0         4   <b>ChunkID</b>          Contains the letters "RIFF" in ASCII form
                               (0x52494646 big-endian form).
4         4   <b>ChunkSize</b>        36 + SubChunk2Size, or more precisely:
                               4 + (8 + SubChunk1Size) + (8 + SubChunk2Size)
                               This is the size of the rest of the chunk 
                               following this number.  This is the size of the 
                               entire file in bytes minus 8 bytes for the
                               two fields not included in this count:
                               ChunkID and ChunkSize.
8         4   <b>Format</b>           Contains the letters "WAVE"
                               (0x57415645 big-endian form).

The "WAVE" format consists of two subchunks: "fmt " and "data":
The "fmt " subchunk describes the sound data's format:

12        4   <b>Subchunk1ID</b>      Contains the letters "fmt "
                               (0x666d7420 big-endian form).
16        4   <b>Subchunk1Size</b>    16 for PCM.  This is the size of the
                               rest of the Subchunk which follows this number.
20        2   <b>AudioFormat</b>      PCM = 1 (i.e. Linear quantization)
                               Values other than 1 indicate some 
                               form of compression.
22        2   <b>NumChannels</b>      Mono = 1, Stereo = 2, etc.
24        4   <b>SampleRate</b>       8000, 44100, etc.
28        4   <b>ByteRate</b>         == SampleRate * NumChannels * BitsPerSample/8
32        2   <b>BlockAlign</b>       == NumChannels * BitsPerSample/8
                               The number of bytes for one sample including
                               all channels. I wonder what happens when
                               this number isn't an integer?
34        2   <b>BitsPerSample</b>    8 bits = 8, 16 bits = 16, etc.
<font color=888888>          2   <b>ExtraParamSize</b>   if PCM, then doesn't exist
          X   <b>ExtraParams</b>      space for extra parameters</font>

The "data" subchunk contains the size of the data and the actual sound:

36        4   <b>Subchunk2ID</b>      Contains the letters "data"
                               (0x64617461 big-endian form).
40        4   <b>Subchunk2Size</b>    == NumSamples * NumChannels * BitsPerSample/8
                               This is the number of bytes in the data.
                               You can also think of this as the size
                               of the read of the subchunk following this 
                               number.
44        *   <b>Data</b>             The actual sound data.
<hr/>
<code><?php 
            highlight_string(file_get_contents(__FILE__));
        ?>
</code></pre>
        
    </body>
</html>