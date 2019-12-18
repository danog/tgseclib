<?php

/**
 * @author    Andreas Fischer <bantu@phpbb.com>
 * @copyright 2015 Andreas Fischer
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 */

use tgseclib\Net\SFTP\Stream;

class Functional_Net_SFTPStreamTest extends Functional_Net_SFTPTestCase
{
    public static function setUpBeforeClass()
    {
        Stream::register();
        parent::setUpBeforeClass();
    }

    public function testFopenFcloseCreatesFile()
    {
        $context = stream_context_create([
            'sftp' => ['session' => $this->sftp],
        ]);
        $fp = fopen($this->buildUrl('fooo.txt'), 'wb', false, $context);
        $this->assertInternalType('resource', $fp);
        fclose($fp);
        $this->assertSame(0, $this->sftp->size('fooo.txt'));
    }

    /**
     * @group github778
     */
    public function testFilenameWithHash()
    {
        $context = stream_context_create([
            'sftp' => ['session' => $this->sftp],
        ]);
        $fp = fopen($this->buildUrl('te#st.txt'), 'wb', false, $context);
        fputs($fp, 'zzzz');
        fclose($fp);

        $this->assertContains('te#st.txt', $this->sftp->nlist());
    }

    /**
     * Tests connection reuse functionality same as ssh2 extension:
     * {@link http://php.net/manual/en/wrappers.ssh2.php#refsect1-wrappers.ssh2-examples}
     */
    public function testConnectionReuse()
    {
        $originalConnectionsCount = count(\tgseclib\Net\SSH2::getConnections());
        $session = $this->sftp;
        $dirs = scandir("sftp://$session/");
        $this->assertCount($originalConnectionsCount, \tgseclib\Net\SSH2::getConnections());
        $this->assertEquals(['.', '..'], array_slice($dirs, 0, 2));
    }

    protected function buildUrl($suffix)
    {
        return sprintf(
            'sftp://via-context/%s/%s',
            $this->sftp->pwd(),
            $suffix
        );
    }
}
