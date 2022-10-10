<?php
namespace App\Services\Report;

interface ReportServiceInterface {

	/** setup filter parameters for report
	 * 
	 * @param array $args         */
	public function setParams(Array $args);

	/** get filter parameters of report
	 * 
	 * @return array */
	public function getParams();

	/** get data table of report
	 * 
	 * @return array */
	public function getTable();

	public function getTimestampFrom();

	public function getTimestampTo();
}