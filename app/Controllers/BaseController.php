<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /*
    |-------------------------------------------------------------------
    | HELPERS
    |-------------------------------------------------------------------
    | Menentukan helper global yang otomatis tersedia di semua controller
    | turunan BaseController. Helper 'url' dipakai untuk base_url() dan
    | site_url(), helper 'form' dipakai untuk old() dan csrf_field(),
    | sedangkan helper 'session' dipakai untuk session().
    | Alur kerja: CI4 memproses property ini saat controller
    | diinisialisasi dan memuat helper sebelum method dijalankan.
    |
    | Tips Debugging:
    | - Jika view login error karena base_url(), old(), csrf_field(), atau
    |   session() tidak dikenali, periksa isi property $helpers ini.
    */
    protected $helpers = ['url', 'form', 'session'];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }
}
