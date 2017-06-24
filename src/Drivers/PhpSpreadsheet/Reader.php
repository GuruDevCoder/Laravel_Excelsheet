<?php

namespace Maatwebsite\Excel\Drivers\PhpSpreadsheet;

use Maatwebsite\Excel\Configuration;
use Maatwebsite\Excel\Reader as ReaderInterface;
use Maatwebsite\Excel\Spreadsheet as SpreadsheetInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet as PhpSpreadsheet;
use Maatwebsite\Excel\Exceptions\InvalidSpreadsheetLoaderException;

class Reader implements ReaderInterface
{
    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var callable|null
     */
    protected $spreadsheetLoader;

    /**
     * @param Configuration $configuration
     * @param callable      $spreadsheetLoader
     *
     * @throws InvalidSpreadsheetLoaderException
     */
    public function __construct(Configuration $configuration, callable $spreadsheetLoader)
    {
        $this->setLoader($spreadsheetLoader);

        $this->configuration = $configuration;
    }

    /**
     * @param string        $filePath
     * @param callable|null $callback
     *
     * @return SpreadsheetInterface|Spreadsheet
     */
    public function load(string $filePath, callable $callback = null): SpreadsheetInterface
    {
        $this->spreadsheet = new Spreadsheet(
            $this->loadSpreadsheet($filePath),
            $this->configuration
        );

        if (is_callable($callback)) {
            $callback($this->spreadsheet);
        }

        return $this->spreadsheet;
    }

    /**
     * @param callable $spreadsheetLoader
     *
     * @return ReaderInterface
     */
    public function setLoader(callable $spreadsheetLoader): ReaderInterface
    {
        $this->spreadsheetLoader = $spreadsheetLoader;

        return $this;
    }

    /**
     * @return callable
     */
    public function getLoader(): callable
    {
        return $this->spreadsheetLoader;
    }

    /**
     * @param string $filePath
     *
     * @return PhpSpreadsheet
     */
    protected function loadSpreadsheet(string $filePath): PhpSpreadsheet
    {
        $loader = $this->getLoader();

        return $loader($filePath);
    }
}
