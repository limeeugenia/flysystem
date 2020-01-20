<?php

declare(strict_types=1);

namespace League\Flysystem\InMemory;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapterTestCase;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;

class InMemoryFilesystemTest extends FilesystemAdapterTestCase
{
    const PATH = 'path.txt';

    /**
     * @before
     */
    public function resetFunctionMocks(): void
    {
        reset_function_mocks();
    }


    /**
     * @test
     */
    public function writing_and_reading_a_file()
    {
        $adapter = $this->adapter();
        $adapter->write(self::PATH, 'contents', new Config());
        $contents = $adapter->read(self::PATH);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function getting_visibility_on_a_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter()->visibility('path.txt');
    }

    /**
     * @test
     */
    public function getting_mimetype_on_a_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter()->mimeType('path.txt');
    }

    /**
     * @test
     */
    public function getting_last_modified_on_a_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter()->lastModified('path.txt');
    }

    /**
     * @test
     */
    public function getting_file_size_on_a_non_existing_file()
    {
        $this->expectException(UnableToRetrieveMetadata::class);
        $this->adapter()->fileSize('path.txt');
    }

    /**
     * @test
     */
    public function deleting_a_file()
    {
        $this->adapter()->write('path.txt', 'contents', new Config());
        $this->assertTrue($this->adapter()->fileExists('path.txt'));
        $this->adapter()->delete('path.txt');
        $this->assertFalse($this->adapter()->fileExists('path.txt'));
    }



    /**
     * @test
     */
    public function deleting_a_directory()
    {
        $adapter = $this->adapter();
        $adapter->write('a/path.txt', 'contents', new Config());
        $adapter->write('a/b/path.txt', 'contents', new Config());
        $adapter->write('a/b/c/path.txt', 'contents', new Config());
        $this->assertTrue($adapter->fileExists('a/b/path.txt'));
        $this->assertTrue($adapter->fileExists('a/b/c/path.txt'));
        $adapter->deleteDirectory('a/b');
        $this->assertTrue($adapter->fileExists('a/path.txt'));
        $this->assertFalse($adapter->fileExists('a/b/path.txt'));
        $this->assertFalse($adapter->fileExists('a/b/c/path.txt'));
//        var_dump(iterator_to_array($adapter->listContents('', false)));
    }

    /**
     * @test
     */
    public function creating_a_directory_does_nothing()
    {
        $this->adapter()->createDirectory('something', new Config());
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function updating_and_reading_a_file()
    {
        $this->adapter()->update(self::PATH, 'contents', new Config());
        $contents = $this->adapter()->read(self::PATH);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function writing_with_a_stream_and_reading_a_file()
    {
        $handle = stream_with_contents('contents');
        $this->adapter()->writeStream(self::PATH, $handle, new Config());
        $contents = $this->adapter()->read(self::PATH);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function updating_with_a_stream_and_reading_a_file()
    {
        $handle = stream_with_contents('contents');
        $this->adapter()->updateStream(self::PATH, $handle, new Config());
        fclose($handle);
        $contents = $this->adapter()->read(self::PATH);
        $this->assertEquals('contents', $contents);
    }

    /**
     * @test
     */
    public function reading_a_stream()
    {
        $this->adapter()->write(self::PATH, 'contents', new Config());
        $contents = $this->adapter()->readStream(self::PATH);
        $this->assertEquals('contents', stream_get_contents($contents));
        fclose($contents);
    }

    /**
     * @test
     */
    public function reading_a_non_existing_file()
    {
        $this->expectException(UnableToReadFile::class);
        $this->adapter()->read('path.txt');
    }

    /**
     * @test
     */
    public function stream_reading_a_non_existing_file()
    {
        $this->expectException(UnableToReadFile::class);
        $this->adapter()->readStream('path.txt');
    }

    /**
     * @test
     */
    public function listing_all_files()
    {
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());
        $adapter->write('a/path.txt', 'contents', new Config());
        $adapter->write('a/b/path.txt', 'contents', new Config());;
        $listing = iterator_to_array($adapter->listContents('/', true));
        $this->assertCount(5, $listing);
        $this->assertContainsEquals(new FileAttributes('path.txt'), $listing);
        $this->assertContainsEquals(new FileAttributes('a/path.txt'), $listing);
        $this->assertContainsEquals(new FileAttributes('a/b/path.txt'), $listing);
        $this->assertContainsEquals(new DirectoryAttributes('a'), $listing);
        $this->assertContainsEquals(new DirectoryAttributes('a/b'), $listing);
    }

    /**
     * @test
     */
    public function listing_non_recursive()
    {
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());
        $adapter->write('a/path.txt', 'contents', new Config());
        $adapter->write('a/b/path.txt', 'contents', new Config());
        $listing = iterator_to_array($adapter->listContents('/', false));
        $this->assertCount(2, $listing);
    }

    /**
     * @test
     */
    public function moving_a_file_successfully()
    {
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());
        $adapter->move('path.txt', 'new-path.txt', new Config());
        $this->assertFalse($adapter->fileExists('path.txt'));
        $this->assertTrue($adapter->fileExists('new-path.txt'));
    }

    /**
     * @test
     */
    public function moving_a_file_with_collision()
    {
        $this->expectException(UnableToMoveFile::class);
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());
        $adapter->write('new-path.txt', 'contents', new Config());
        $adapter->move('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function trying_to_move_a_non_existing_file()
    {
        $this->expectException(UnableToMoveFile::class);
        $this->adapter()->move('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function copying_a_file_successfully()
    {
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());
        $adapter->copy('path.txt', 'new-path.txt', new Config());
        $this->assertTrue($adapter->fileExists('path.txt'));
        $this->assertTrue($adapter->fileExists('new-path.txt'));
    }

    /**
     * @test
     */
    public function trying_to_copy_a_non_existing_file()
    {
        $this->expectException(UnableToCopyFile::class);
        $this->adapter()->copy('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function copying_a_file_with_collision()
    {
        $this->expectException(UnableToCopyFile::class);
        $adapter = $this->adapter();
        $adapter->write('path.txt', 'contents', new Config());
        $adapter->write('new-path.txt', 'contents', new Config());
        $adapter->copy('path.txt', 'new-path.txt', new Config());
    }

    /**
     * @test
     */
    public function checking_for_metadata()
    {
        mock_function('time', 1234);
        $adapter = $this->adapter();
        $adapter->write(
            self::PATH,
            file_get_contents(__DIR__.'/../../test_files/flysystem.svg'),
            new Config()
        );

        $this->assertTrue($adapter->fileExists(self::PATH));
        $this->assertEquals(753, $adapter->fileSize(self::PATH)->fileSize());
        $this->assertEquals(1234, $adapter->lastModified(self::PATH)->lastModified());
        $this->assertEquals('image/svg', $adapter->mimeType(self::PATH)->mimeType());
    }

    function createFilesystemAdapter(): FilesystemAdapter
    {
        return new InMemoryFilesystem();
    }
}