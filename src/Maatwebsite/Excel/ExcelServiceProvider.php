<?php namespace Maatwebsite\Excel;

use Maatwebsite\Excel\Classes\PHPExcel;
use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Readers\Html;
use Maatwebsite\Excel\Readers\LaravelExcelReader;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Maatwebsite\Excel\Parsers\ViewParser;

class ExcelServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */

	public function boot()
	{
		$this->package('maatwebsite/excel');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->bindReaders();
		$this->bindParsers();
		$this->bindPHPExcelClass();
		$this->bindClasses();
		$this->bindWriters();
		$this->bindExcel();
	}

	/**
	 * Bind PHPExcel classes
	 * @return [type] [description]
	 */
	protected function bindPHPExcelClass()
	{
		// Bind the PHPExcel class
		$this->app['phpexcel'] = $this->app->share(function($app) {
			return new PHPExcel();
		});
	}

	/**
	 * Bind Classes
	 * @return [type] [description]
	 */
	protected function bindClasses()
	{

	}

	/**
	 * Bind writers
	 * @return [type] [description]
	 */
	protected function bindReaders()
	{

		// Bind the laravel excel reader
		$this->app['excel.reader'] = $this->app->share(function($app) {
			return new LaravelExcelReader($app['files']);
		});

		// Bind the html reader class
		$this->app['excel.readers.html'] = $this->app->share(function($app) {
			return new Html();
		});
	}

	/**
	 * Bind writers
	 * @return [type] [description]
	 */
	protected function bindParsers()
	{
		$this->app['excel.parsers.view'] = $this->app->share(function($app) {
			return new ViewParser($app['excel.readers.html']);
		});
	}

	/**
	 * Bind writers
	 * @return [type] [description]
	 */
	protected function bindWriters()
	{
		$this->app['excel.writer'] = $this->app->share(function($app) {
			return new LaravelExcelWriter($app->make('Response'), $app['files']);
		});
	}

	/**
	 * Bind Excel class
	 * @return [type] [description]
	 */
	protected function bindExcel()
	{
		// Bind the Excel class and inject its dependencies
		$this->app['excel'] = $this->app->share(function($app)
        {
            return new Excel($app['phpexcel'], $app['excel.reader'], $app['excel.writer'], $app['excel.parsers.view'], $app['config'], $app['view'], $app['files']);
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('excel');
	}

}