<?php
use Illuminate\Support\Str;
defined ( 'DEBIT' ) or define ( 'DEBIT', '0' );
defined ( 'CREDIT' ) or define ( 'CREDIT', '1' );

// DB TableNames
defined ( 'POSTINGS' ) or define ( 'POSTINGS', 'postings' );
defined ( 'ACCOUNTS' ) or define ( 'ACCOUNTS', 'accounts' );
defined ( 'INVOICES_TBL' ) or define ( 'INVOICES_TBL', 'invoices' );
defined ( 'EVENT_XML_DATA' ) or define ( 'EVENT_XML_DATA', 'event_xml_data' );
defined ( 'INVOICE_JOURNAL_EVENTS' ) or define ( 'INVOICE_JOURNAL_EVENTS', 'invoice_journal_event' );
defined ( 'ACCOUNT_TYPES' ) or define ( 'ACCOUNT_TYPES', 'account_types' );
defined ( 'FINANCE_TYPES' ) or define ( 'FINANCE_TYPES', 'finance_types' );
defined ( 'MEDIUM_TYPES' ) or define ( 'MEDIUM_TYPES', 'medium_types' );
defined ( 'CURRENCY' ) or define ( 'CURRENCY', 'currency' );
defined ( 'CURRENCIES' ) or define ( 'CURRENCIES', 'currencies' );
defined ( 'JOURNAL_EVENTS' ) or define ( 'JOURNAL_EVENTS', 'journal_events' );
defined ( 'JOURNAL_EVENT_TYPES' ) or define ( 'JOURNAL_EVENT_TYPES', 'journal_event_types' );
defined ( 'MEDIUM_INFO' ) or define ( 'MEDIUM_INFO', 'medium_info' );
defined ( 'MERCHANTS' ) or define ( 'MERCHANTS', 'merchants' );
defined ( 'PROGRAMS' ) or define ( 'PROGRAMS', 'programs' );
defined ( 'PROGRAM_MERCHANT' ) or define ( 'PROGRAM_MERCHANT', 'program_merchant' );

defined ( 'PROGRAM_PATHS' ) or define ( 'PROGRAM_PATHS', 'program_paths' );
defined ( 'PROGRAMS_EXTRA' ) or define ( 'PROGRAMS_EXTRA', 'programs_extra' );
defined ( 'PROGRAM_TYPES_TBL' ) or define ( 'PROGRAM_TYPES_TBL', 'program_types' );
defined ( 'STATE_TYPES_TBL' ) or define ( 'STATE_TYPES_TBL', 'state_types' );
defined ( 'TOKENS' ) or define ( 'TOKENS', 'tokens' );
defined ( 'TOKEN_TYPES' ) or define ( 'TOKEN_TYPES', 'token_types' );
defined ( 'PROGRAM_STATE_DELETED' ) or define ( 'PROGRAM_STATE_DELETED', 'Deleted' );
defined ( 'TOKEN_TYPE_SIGNUP' ) or define ( 'TOKEN_TYPE_SIGNUP', 'signup' );

defined ( 'CONFIG_FIELDS' ) or define ( 'CONFIG_FIELDS', 'config_fields' );
defined ( 'CUSTOM_FIELD_TYPES' ) or define ( 'CUSTOM_FIELD_TYPES', 'custom_field_types' );
defined ( 'CONFIG_FIELDS_HAS_RULES' ) or define ( 'CONFIG_FIELDS_HAS_RULES', 'config_fields_has_rules' );
defined ( 'CUSTOM_FIELD_RULES' ) or define ( 'CUSTOM_FIELD_RULES', 'custom_field_rules' );
defined ( 'PROGRAMS_CONFIG_FIELDS' ) or define ( 'PROGRAMS_CONFIG_FIELDS', 'programs_config_fields' );
defined ( 'DOMAINS' ) or define ( 'DOMAINS', 'domains' );
defined ( 'ACCOUNT_HOLDERS' ) or define ( 'ACCOUNT_HOLDERS', 'account_holders' );
defined ( 'DOMAINS_HAS_PROGRAMS' ) or define ( 'DOMAINS_HAS_PROGRAMS', 'domains_has_programs' );


defined ( 'JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING' ) or define ( 'JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING', 'Reversal program pays for monies pending' );
defined ( 'JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE' ) or define ( 'JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE', 'Reversal program pays for deposit fee' );
defined ( 'ALLOWED_HTML_TAGS' ) or define ( 'ALLOWED_HTML_TAGS', '<strong><b><p><br>' );
defined ( 'ADMIN_FEE_CALC_PARTICIPANTS' ) or define ('ADMIN_FEE_CALC_PARTICIPANTS', 'participants' );
defined ( 'ADMIN_FEE_CALC_UNITS' ) or define ('ADMIN_FEE_CALC_UNITS', 'units' );
defined ( 'ADMIN_FEE_CALC_CUSTOM' ) or define ('ADMIN_FEE_CALC_CUSTOM', 'custom' );


// Database Tables names as CONSTANTS
defined ( 'TBL_ORDER_LINE_ITEMS' ) or define ('TBL_ORDER_LINE_ITEMS', 'order_line_items' );
defined ( 'TBL_MEDIUM_INFO' ) or define ('TBL_MEDIUM_INFO', 'medium_info' );
defined ( 'TBL_MERCHANTS' ) or define ('TBL_MERCHANTS', 'merchants' );
defined ( 'TBL_STATE_TYPES' ) or define ('TBL_STATE_TYPES', 'statuses' );
defined ( 'TBL_USERS' ) or define ('TBL_USERS', 'users' );

defined ( 'MERCHANT_PATHS' ) or define ('MERCHANT_PATHS', 'merchant_paths' );

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

// if(!function_exists('_flatten'))  {
//     function _flatten($collection, &$newCollection)
//     {
//         foreach( $collection as $model ) {
//             $children = $model->children;
//             unset($model->children);
//             if( !$newCollection ) {
//                 $newCollection = collect([$model]);
//             }   else {
//                 $newCollection->push($model);
//             }
//             if (!$children->isEmpty()) {
//                 $newCollection->merge(_flatten($children, $newCollection));
//             }
//         }
//     }
// }
if(!function_exists('_flatten'))  {
    function _flatten($collection)
    {
		if(!isset($newCollection)) $newCollection = collect();

        foreach( $collection as $model ) {
            $children = $model->children;
            unset($model->children);
            $newCollection = $newCollection->push($model);
            if (!$children->isEmpty()) {
                $newCollection = $newCollection->merge(_flatten($children));
            }
        }
		return $newCollection;
    }
}

if(!function_exists('_tree_flatten'))  {
    function _tree_flatten($collection, $depth = 0, $path = 0)
    {
        if(!isset($newCollection)) $newCollection = collect();
        $depth++;
        foreach( $collection as $key => $model ) {
            $children = clone $model->children;
            unset($model->children);
            $tmpPath = $path ? explode(',', $path) : [];
            $tmpPath[] = $model->parent_id;
            $model->dinamicPath = implode(',', $tmpPath);

            $search = $newCollection->search(function ($item) use ($model) {
                return $item->id === $model->id;
            });

            if ($search === false){
                $model->dinamicDepth = $depth;
                $newCollection = $newCollection->push($model);
            }

            if (!$children->isEmpty()) {
                $newCollection = $newCollection->merge(_tree_flatten($children, $depth, $model->dinamicPath));
            }
        }
        return $newCollection;
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
if (! function_exists ( 'toSql' ))
{
	function toSql($log)
    {
		$newLog = [];
		foreach($log as $query)	{
			$newLog[] = \Str::replaceArray(
				'?', array_map(function ($a) { return is_numeric($a) ? $a : "'$a'"; }, $query['bindings']),
				$query['query']
			);
		}
		return $newLog;
	}
}

if(!function_exists('getEntrataAcademicYear')) {
	function getEntrataAcademicYear($date = "")
	{
		if(empty($date)){
			$date = date('Y-m-d');
		}
		$fiscalYear = date('Y-09-01');
		if(date("m", strtotime($date)) < 9){
			$year = date('Y')-1;
			$fiscalYear = $year.'-09-01';
		}
		return $fiscalYear;
	}
}
if(!function_exists('compare_floats')) {
    function compare_floats($a, $b) {
        $epsilon = 0.00001;
        if (abs ( $a - $b ) < $epsilon)
            return 0;
        return ($a > $b) ? - 1 : 1;
    }
}

if(!function_exists('childrenizeCollection'))  {
    function childrenizeCollection($collection)
    {
        $collection->transform(function ($value) {
            $value = childrenizeModel($value);
            return $value;
        });
        return $collection;
    }
}

function childrenizeModel( $model )
{
    if( isset($model->childrenMinimal) )
    {
        $model->children = $model->childrenMinimal;
        if( $model->children->isNotEmpty() )
        {
            $model->children = childrenizeCollection($model->children);
        }
        unset($model->childrenMinimal);
    }

    return $model;
}

if (! function_exists ( "cast_fieldtypes" )) {
	function cast_fieldtypes($record, $fieldTypes) {
		foreach ( $record as $fieldName => $value ) {
			if (isset ( $fieldTypes [$fieldName] )) {
				$type = $fieldTypes [$fieldName];
				$value = $record->$fieldName;
				switch ($type) {
					case 'boolean' :
					case 'bool' :
						$value = ( bool ) $value;
						break;
					case 'integer' :
					case 'int' :
						$value = ( int ) $value;
						break;
					case 'float' :
						$value = ( float ) $value;
						break;
					case 'string' :
						$value = ( string ) $value;
						break;
					default :
						break;
				}
				$record->$fieldName = $value;
			}
		}
		return $record;
	}
}

if (! function_exists ( 'sort_programs_by_rank_for_view' )) {
	function sort_programs_by_rank_for_view(&$sorted_programs, $programs) {
		foreach ( $programs as $program ) {
			$path = explode ( ',', $program->rank );
			if (count ( $path ) > 1) {
				$index = array_shift ( $path );
				$program->path = count($path);
				$program->rank = implode ( ',', $path );
				if (! isset ( $sorted_programs [$index] ['sub_programs'] )) {
					$sorted_programs [$index] ['sub_programs'] = array ();
				}
				$sub_program_array = sort_programs_by_rank_for_view ( $sorted_programs [$index] ['sub_programs'], array (
						$program
				) );
			} else {
				$sorted_programs [$path [0]] ['program'] = $program;
			}
		}
		return $sorted_programs;
	}
}

if (! function_exists ( 'sort_result_by_rank' )) {
	function sort_result_by_rank(&$sorted_result, $result, $keyName = 'items') {
		foreach ( $result as $row ) {
			$path = explode ( ',', $row->rank );
			if (count ( $path ) > 1) {
				$index = array_shift ( $path );
				$row->path = count($path);
				$row->rank = implode ( ',', $path );
				if (! isset ( $sorted_result[$index]['sub_'.$keyName])) {
					$sorted_result[$index]['sub_'.$keyName] = [];
				}
				$sub_program_array = sort_result_by_rank ( $sorted_result[$index]['sub_' . $keyName], array (
					$row
                ), $keyName );
			} else {
				$sorted_result[$path[0]][$keyName] = $row;
			}
		}
		return $sorted_result;
	}
}
function compute_program_fee_by_type($key, $program, $amount) {
    if( !isset($program[$key]) || (float) $program[$key] <= 0 ) return 0;
    $v_fee = $program[$key] / 100.0;
    $v_fee_amount = $v_fee * $amount;
    return $v_fee_amount;
}
if(!function_exists('extract_fields_from_obj'))   {
    function extract_fields_from_obj( mixed $object, array $fields)   {
        $arr = [];
        $object = (array) $object;
        foreach( $fields as $field) {
            if( isset($object[$field]) )    {
                $arr[$field] = $object[$field];
            }
        }
        return (object) $arr;
    }
}
if(!function_exists('is_valid_json'))   {
    function is_valid_json($str) {
        json_decode($str);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (! function_exists('camel_case')) {
    function camel_case($value)
    {
        return Str::camel($value);
    }
}

if (! function_exists('cronlog')) {
    function cronlog( $msg )
    {
        return Illuminate\Support\Facades\Log::channel('cron')->info( $msg );
    }
}
if (! function_exists('getMilestoneOptions')) {
    function getMilestoneOptions()  {
        $options = [];
        for( $i=1;$i<=30;$i++ )   {
            $options[$i] = "$i Year" . ($i > 1 ? 's' : '');
        }
        return $options;
    }
}

if (! function_exists ( 'filterNonPrintable' ))
{
    /**
     * @desc Codes 32-127 are common for all the different variations of the ASCII table, they are called printable characters
     *       FILTER_FLAG_STRIP_LOW - Strip characters with ASCII value below 32
     *       FILTER_FLAG_STRIP_HIGH - Strip characters with ASCII value above 127
     * @param $string
     * @return string
     */
    function filterNonPrintable($string)
    {
        $string = filter_var($string, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
        return $string;
    }
}
