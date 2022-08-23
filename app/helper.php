<?php
defined ( 'DEBIT' ) or define ( 'DEBIT', '0' );
defined ( 'CREDIT' ) or define ( 'CREDIT', '1' );
defined ( 'JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING' ) or define ( 'JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING', 'Reversal program pays for monies pending' );
defined ( 'JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE' ) or define ( 'JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE', 'Reversal program pays for deposit fee' );

if(!function_exists('pr'))  {
    function pr($d)    {
        $appPath = app_path();
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $relativePath = str_replace( $appPath, '', $caller['file']);
        $file_line = $relativePath . "(line " . $caller['line'] . ")\n";
        print_r($file_line);
        print_r($d);
        print_r("\n\n");
    }
}

if(!function_exists('pre'))  {
    function pre($d)    {
        $appPath = app_path();
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $relativePath = str_replace( $appPath, '', $caller['file']);
        $file_line = $relativePath . "(line " . $caller['line'] . ")\n";
        print_r($file_line);
        echo '<pre>';
        print_r($d);
        echo '</pre>';
    }
}

function generate_unique_id($char = 12)
{
    $rand = strtoupper(substr(uniqid(sha1(time())),0,$char));
    return date("ymds") .'-'. $rand;
}

if(!function_exists('get_merchant_by_id'))  {
    function get_merchant_by_id($merchants, $merchant_id)   {
        foreach($merchants as $merchant)   {
            if($merchant->id == $merchant_id) return $merchant;
        }
    }
}
if(!function_exists('isValidDate'))  {
    function isValidDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

if(!function_exists('_flatten'))  {
    function _flatten($collection, &$newCollection)
    {
        foreach( $collection as $model ) {
            $children = $model->children;
            unset($model->children);
            if( !$newCollection ) {
                $newCollection = collect([$model]);
            }   else {
                $newCollection->push($model);
            }
            if (!$children->isEmpty()) {
                $newCollection->merge(_flatten($children, $newCollection));
            }
        }
    }
}
if(!function_exists('collectIdsInATree'))  {
    function collectIdsInATree($treeNodes, &$ids)
    {
        foreach( $treeNodes as $node)   {
            array_push($ids, $node['id']);
            if($node['children'])  {
                $collectedIds = collectIdsInATree($node['children'], $ids);
                if( $collectedIds ) {
                    $ids = array_merge($ids, $collectedIds);
                }
            }
        }
    }
}

if (! function_exists ( 'account_type_parser' )) {
	function account_type_parser($data = array(), $report_type = "") {
		$account_type_data = array (
				'Cash' => array (
						DEBIT => 1,
						CREDIT => - 1 
				),
				'Equity' => array (
						DEBIT => 1,
						CREDIT => - 1 
				),
				'Escrow' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Gift Codes Available' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Gift Codes Pending' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Gift Codes Redeemed' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Gift Codes Spent' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Income' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Monies Awarded' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Monies Deposits' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Monies Due to Owner' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Monies Fees' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Monies Paid to Progam' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Monies Pending' => array (
						DEBIT => 1,
						CREDIT => - 1 
				),
				'Monies Redeemed' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Monies Shared' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Monies Transaction' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Monies Setup' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Points Available' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Points Awarded' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Points Pending' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Points Redeemed' => array (
						DEBIT => - 1,
						CREDIT => 1 
				),
				'Points Fees' => array (
						DEBIT => - 1,
						CREDIT => 1 
				) 
		);
		// loop the data passed
		foreach ( $data as $key => $value ) {
			// check if the account type name exist
			if (isset ( $account_type_data [$value->account_type_name] )) {
				$data [$key]->amount = $data [$key]->amount * $account_type_data [$value->account_type_name] [$value->is_credit];
				if ($report_type == 'trial balance') {
					$data [$key]->amount = $data [$key]->amount * - 1;
				}
			}
		}
		return $data;
	
	}
}
