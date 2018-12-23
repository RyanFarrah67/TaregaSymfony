<?php

namespace Tests\Functional\AppBundle\Controller\Controller\Profile\Song;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use AppBundle\Entity\Song;

class SongControllerTest extends WebTestCase
{

    /**
     * @var string
     */
    protected $audioUploadsDirectory;

    /**
     * @var string
     */
    public static $audioStorageDirectory;

    /**
     * @var EntityManagerInterface 
     */
    protected $entityManager;

    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->client = $client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'username',
            'PHP_AUTH_PW'   => 'password',
        ));

        $container = $client->getContainer();

        $this->audioUploadsDirectory = $container->getParameter('audio_uploads_directory');

        self::$audioStorageDirectory = $container->getParameter('audio_directory');

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    public function testNewSongSuccess()
    {
        $client = $this->client;

        $crawler = $client->request('GET', '/profile/new');

        $buttonCrawlerNode = $crawler->selectButton('new_song_save');

        $form = $buttonCrawlerNode->form();

        $form['new_song[audioFile]']->upload($this->audioUploadsDirectory . 'about_a_girl.mp3');

        $form['new_song[audioName]'] = 'name';

        $client->submit($form);

        $response = $client->getResponse();

        $this->assertContains('Vous avez bien enregistré votre musique', $response->getContent());
        
    }


    public function testNewSongFailure()
    {
        $client = $this->client;

        $crawler = $client->request('GET', '/profile/new');

        $buttonCrawlerNode = $crawler->selectButton('new_song_save');

        $form = $buttonCrawlerNode->form();

        $form['new_song[audioFile]']->upload($this->audioUploadsDirectory . 'error/' .  'error');

        $form['new_song[audioName]'] = 'sdsd';

        $client->submit($form);

        $response = $client->getResponse();

        $this->assertThat(
            $response->getContent(),
            $this->logicalNot(
                $this->stringContains('Vous avez bien enregistré votre musique')
            )
        );
        
    }

    public function testEditSongSuccess() 
    {

        $client = $this->client;

        $song = $this->entityManager->getRepository(Song::class)->findOneByAudioName('name');

        $crawler = $client->request('GET', '/profile/edit/' . $song->getId());

        $buttonCrawlerNode = $crawler->selectButton('edit_song_save');

        $form = $buttonCrawlerNode->form();

        $form['edit_song[audioFile]']->upload($this->audioUploadsDirectory . 'about_a_girl.mp3');

        $form['edit_song[audioName]'] = 'name';

        $client->submit($form);

        $response = $client->getResponse();

        $this->assertContains('Vous avez bien modifié votre musique', $response->getContent());
    }

    public function testEditSongFailure() 
    {

        $client = $this->client;

        $song = $this->entityManager->getRepository(Song::class)->findOneByAudioName('name');

        $crawler = $client->request('GET', '/profile/edit/' . $song->getId());

        $buttonCrawlerNode = $crawler->selectButton('edit_song_save');

        $form = $buttonCrawlerNode->form();

        $form['edit_song[audioFile]']->upload($this->audioUploadsDirectory . 'error/' .  'error');

        $form['edit_song[audioName]'] = '';

        $client->submit($form);

        $response = $client->getResponse();

        $this->assertThat(
            $response->getContent(),
            $this->logicalNot(
                $this->stringContains('Vous avez bien modifié votre musique')
            )
        );
    }

    public static function tearDownAfterClass()
    {
        foreach (glob(self::$audioStorageDirectory . '*') as $folder) {
            $folder = $folder . '/';
            if(is_dir($folder)) {
                if($dir = opendir($folder)) {
                    while (false !== ($entry = readdir($dir))) {
                        $filePath = $folder . $entry;
                        if(is_file($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
            }
        }
    }
}
