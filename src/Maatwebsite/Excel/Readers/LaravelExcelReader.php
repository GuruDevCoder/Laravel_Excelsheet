<?php namespace Maatwebsite\Excel\Readers;

use \PHPExcel_IOFactory;
use Illuminate\Filesystem\Filesystem;
use Maatwebsite\Excel\Parsers\ExcelParser;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;
use Illuminate\Support\Collection;

class LaravelExcelReader {

    /**
     * Excel object
     * @var [type]
     */
    public $excel;

    /**
     * Spreadsheet writer
     * @var [type]
     */
    public $reader;

    /**
     * The file to read
     * @var [type]
     */
    public $file;

    /**
     * Spreadsheet title
     * @var [type]
     */
    public $title;

    /**
     * Default extension
     * @var string
     */
    public $ext = 'xls';

    /**
     * Default format
     * @var [type]
     */
    public $format;

    /**
     * The parsed file
     * @var [type]
     */
    public $parsed;

    /**
     * Delimtier
     * @var [type]
     */
    public $delimiter;

    /**
     * Calculate [true/false]
     * @var [type]
     */
    public $calculate;

    /**
     * Limit data
     * @var boolean
     */
    public $limit = false;

    /**
     * Slug seperator
     * @var string
     */
    public $seperator = '_';

     /**
     * Ignore empty cells
     * @var boolean
     */
    public $ignoreEmpty = false;

    /**
     * Format dates
     * @var boolean
     */
    public $formatDates = true;

    /**
     * Default date format
     * @var string
     */
    public $dateFormat = false;

    /**
     * Construct new writer
     * @param Response   $response [description]
     * @param FileSystem $files    [description]
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Load a file
     * @param  [type]  $file            [description]
     * @param  boolean $firstRowAsIndex [description]
     * @param  string  $inputEncoding   [description]
     * @return [type]                   [description]
     */
    public function load($file)
    {
        // init the loading
        $this->_init($file);

        // Load the file
        $this->excel = $this->reader->load($this->file);

        // Return itself
        return $this;
    }

    /**
     * Take x rows
     * @param  [type] $amount [description]
     * @return [type]         [description]
     */
    public function take($amount)
    {
        $this->limit = $amount;
        return $this;
    }

    /**
     * Limit the results by x
     * @param  [type] $amount [description]
     * @return [type]         [description]
     */
    public function limit($amount)
    {
        return $this->take($amount);
    }

    /**
     * Return all sheets/rows
     * @return [type] [description]
     */
    public function all()
    {
        return $this->get();
    }

    /**
     * Get all sheets/rows
     * @return [type] [description]
     */
    public function get($columns = array())
    {
        $this->_parseFile($columns);
        return $this->parsed;
    }

    /**
     * Each
     * @param  [type] $callback [description]
     * @return [type]           [description]
     */
    public function each($callback)
    {
        return $this->get()->each($callback);
    }

    /**
     *
     *  Parse the file to an array.
     *
     *  @return array $this->parsed The parsed array
     *
     */
    public function toArray($columns = array())
    {
        return (array) $this->get($columns)->toArray();
    }

    /**
     *
     *  Parse the file to an object.
     *
     *  @return obj $this->parsed The parsed object
     *
     */
    public function toObject($columns = array())
    {
        return $this->get($columns);
    }

    /**
     *
     *  Dump the parsed file to a readable array
     *
     *  @return array $this->parsed The parsed array
     *
     */
    public function dump($columns = array(), $die = false)
    {
        echo '<pre class="container" style="background: #f5f5f5; border: 1px solid #e3e3e3; padding:15px;">';
            $die ? dd($this->get($columns)) : var_dump($this->get($columns));
        echo '</pre>';
    }

    /**
     * Die and dump
     * @return [type] [description]
     */
    public function dd($columns = array())
    {
        return $this->dump($columns, true);
    }

    /**
     * Init the loading
     * @param  [type] $file            [description]
     * @param  [type] $firstRowAsIndex [description]
     * @param  [type] $inputEncoding   [description]
     * @return [type]                  [description]
     */
    protected function _init($file)
    {
        // Set the extension
        $this->_setFile($file)
              ->setExtension()
              ->setTitle()
              ->_setFormat()
              ->_setReader();
    }

    /**
     * Inject the excel object
     * @param  [type] $excel [description]
     * @return [type]        [description]
     */
    public function injectExcel($excel)
    {
        $this->excel = $excel;
        $this->_reset();
    }

    /**
     * Set the file
     * @param [type] $file [description]
     */
    protected function _setFile($file)
    {
        // check if we have a correct path
        if(!realpath($file))
            $file = base_path($file);

        $this->file = $file;
        return $this;
    }

    /**
     * Set the spreadsheet title
     * @param [type] $title [description]
     */
    public function setTitle($title = false)
    {
        $this->title = $title ? $title : basename($this->file, '.' . $this->ext);
        return $this;
    }

    /**
     * Set extension
     * @param [type] $ext [description]
     */
    public function setExtension($ext = false)
    {
        $this->ext = $ext ? $ext: $this->filesystem->extension($this->file);
        return $this;
    }

    /**
     * Set the date format
     * @param str $format The date format
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;
        return $this;
    }

    /**
     * Enable/disable date formating
     * @param  bool $boolean True/false
     */
    public function formatDates($boolean, $format = false)
    {
        $this->formatDates = $boolean;
        $this->setDateFormat($format);
        return $this;
    }

    /**
     *
     *  Set default calculate
     *
     *  @param bool $do Calculate yes or no
     *  @return $this
     *
     */
    public function calculate($do = true)
    {
        $this->calculate = $do;
        return $this;
    }

    /**
     * Set the write format
     */
    protected function _setFormat()
    {
        $this->format = PHPExcel_IOFactory::identify($this->file);
        return $this;
    }

    /**
     * Parse the file
     * @return [type] [description]
     */
    protected function _parseFile($columns = array())
    {
        $parser = new ExcelParser($this);
        $this->parsed = $parser->parseFile($columns);
    }

    /**
     * Set the writer
     */
    protected function _setReader()
    {
        // Init the reader
        $this->reader = PHPExcel_IOFactory::createReader($this->format);
        return $this;
    }

    /**
     * Reset the writer
     * @return [type] [description]
     */
    protected function _reset()
    {
        $this->excel->disconnectWorksheets();
    }

    /**
     * Dynamically call methods
     * @param  [type] $method [description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function __call($method, $params)
    {
        // Call a php excel method
        if(method_exists($this->excel, $method))
        {
            // Call the method from the excel object with the given params
            return call_user_func_array(array($this->excel, $method), $params);
        }

        // If it's a reader method
        elseif(method_exists($this->reader, $method))
        {
            // Call the method from the excel object with the given params
            return call_user_func_array(array($this->reader, $method), $params);
        }

        throw new LaravelExcelException('[ERROR] Reader method ['. $method .'] does not exist.');

    }

}