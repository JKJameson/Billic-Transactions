<?php
class Transactions {
	public $settings = array(
		'name' => 'Transactions',
		'admin_menu_category' => 'Accounting',
		'admin_menu_name' => 'Transactions',
		'description' => 'Lists the payments of invoices and allows expenditures to be entered into Billic.',
		'admin_menu_icon' => '<i class="icon-money-banknote"></i>',
		'permissions' => array(
			'Transactions_Add',
			'Transactions_Delete',
			'Transactions_Edit'
		) ,
	);
	function admin_area() {
		global $billic, $db;
?>
		<script type="text/javascript">
		<!--
		function checkAll(bx, name) {
			var input = document.getElementsByTagName('input');
			for (var i = 0; i < input.length; i++) {
				if (input[i].type == 'checkbox' && input[i].name.split('[')[0] === name) {
					input[i].checked = bx.checked;
				}                
			}
		}
		//-->
		</script>
		<?php
		if (isset($_POST['add'])) {
			if (!$billic->user_has_permission($billic->user, 'Transactions_Add')) {
				err('You do not have permission to add transactions');
			}
			$timestamp = strtotime($_POST['date']);
			if (empty($_POST['date']) || $timestamp == - 1) {
				$billic->errors[] = 'Date is required';
			} else if (empty($_POST['description'])) {
				$billic->errors[] = 'Description is required';
			} else if (empty($_POST['amount'])) {
				$billic->errors[] = 'Amount is required';
			} else if ($_POST['amount'] < 0.01) {
				$billic->errors[] = 'Amount must be greater than 0.01';
			} else {
				$id = $db->insert('transactions', array(
					'date' => $timestamp,
					'description' => $_POST['description'],
					'amount' => (0 - $_POST['amount']) ,
					'vatrate' => $_POST['vatrate'],
					'transid' => $_POST['gateway_id'],
					'resale' => $_POST['resale'],
					'type' => $_POST['type'],
					'eu' => $_POST['eu'],
					'resale' => $_POST['resale'],
				));
				$_status = array(
					'added',
					'Transaction ID ' . $id . ' (' . $_POST['date'] . ')'
				);
			}
		}
		if (isset($_POST['delete'])) {
			if (!$billic->user_has_permission($billic->user, 'Transactions_Delete')) {
				err('You do not have permission to delete transactions');
			}
			if (empty($_POST['ids'])) {
				$billic->errors[] = 'No transactions were selected to be deleted.';
			} else {
				foreach ($_POST['ids'] as $id => $crap) {
					$db->q('DELETE FROM `transactions` WHERE `id` = ?', $id);
				}
				$_status = 'deleted';
			}
		}
		if (isset($_POST['edit'])) {
			if (!$billic->user_has_permission($billic->user, 'Transactions_Edit')) {
				err('You do not have permission to edit transactions');
			}
			if (isset($_POST['update'])) {
				foreach ($_POST['date'] as $id => $date) {
					$date = strtotime($date);
					if (!$date) {
						$billic->error('Unable to change transaction ' . $id . ' because the date is invalid');
						continue;
					}
					$db->q('UPDATE `transactions` SET `date` = ?, `description` = ?, `amount` = ?, `vatrate` = ?, `gateway` = ?, `transid` = ?, `resale` = ?, `eu` = ?, `type` = ? WHERE `id` = ?', $date, $_POST['description'][$id], $_POST['amount'][$id], $_POST['vatrate'][$id], $_POST['gateway'][$id], $_POST['transid'][$id], $_POST['resale'][$id], $_POST['eu'][$id], $_POST['type'][$id], $id);
				}
				$billic->status = 'updated';
				$_POST = json_decode(base64_decode($_POST['search_post']) , true);
			} else {
				if (empty($_POST['ids'])) {
					err('No transactions were selected to be edited.');
				}
				$billic->set_title('Admin/Edit Transactions');
				echo '<h1><i class="icon-money"></i> Edit Transactions</h1>';
				$billic->show_errors();
				echo '<form method="POST"><input type="hidden" name="edit" value="1"><input type="hidden" name="search_post" value="' . $_POST['search_post'] . '">';
				echo '<table class="table table-striped"><tr><th>Date</th><th>Description</th><th>Type</th><th>Amount</th><th>VAT</th><th>Intra-EU</th><th>Resale</th><th>Gateway</th><th>Gateway ID</th></tr>';
				$i = 0;
				foreach ($_POST['ids'] as $id => $crap) {
					$t = $db->q('SELECT * FROM `transactions` WHERE `id` = ?', $id);
					$t = $t[0];
					echo '<tr><td><input type="text" class="form-control" id="date_' . $i . '" name="date[' . $t['id'] . ']" value="' . safe(date('Y-m-d', $t['date'])) . '" style="width: 85px"></td><td><input type="text" class="form-control" name="description[' . $t['id'] . ']" value="' . safe($t['description']) . '"></td><td><select class="form-control" name="type[' . $t['id'] . ']">';
					echo '<option value="service"' . ($t['type'] == 'service' ? ' selected' : '') . '>Service</option>';
					echo '<option value="goods"' . ($t['type'] == 'goods' ? ' selected' : '') . '>Goods</option>';
					if ($t['type'] == '') {
						echo '<option value=""' . ($t['type'] == '' ? ' selected' : '') . '></option>';
					}
					echo '</select></td><td><input type="text" class="form-control" name="amount[' . $t['id'] . ']" value="' . safe($t['amount']) . '" style="width: 75px"></td><td><input type="text" class="form-control" name="vatrate[' . $t['id'] . ']" value="' . safe($t['vatrate']) . '" style="width: 50px"></td><td><input type="checkbox" name="eu[' . $t['id'] . ']"' . ($t['eu'] == 1 ? ' checked' : '') . ' value="1"></td><td><input type="checkbox" name="resale[' . $t['id'] . ']"' . ($t['resale'] == 1 ? ' checked' : '') . ' value="1"></td><td><input type="text" class="form-control" name="gateway[' . $t['id'] . ']" value="' . safe($t['gateway']) . '"></td><td><input type="text" class="form-control" name="transid[' . $t['id'] . ']" value="' . safe($t['transid']) . '"></td></tr>';
					$i++;
				}
				echo '<tr><td colspan="20" align="center"><input type="submit" class="btn btn-success" name="update" value="Update &raquo;"></td></tr>';
				echo '</table></form>';
				echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/css/bootstrap-datepicker.min.css">';
				echo '<script>addLoadEvent(function() { $.getScript( "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/js/bootstrap-datepicker.min.js", function( data, textStatus, jqxhr ) { ';
				for ($x = 0;$x <= $i;$x++) {
					echo '$( "#date_' . $x . '" ).datepicker({ format: "yyyy-mm-dd" }); ';
				}
				echo '}); });</script>';
				exit;
			}
		}

		$billic->module('ListManager');
		$billic->modules['ListManager']->configure(array(
			'search' => array(
				'description' => 'text',
				'gateway_id' => 'text',
				'date_from' => 'date',
				'amount' => 'text',
				'show' => array(
					'all',
					'payments',
					'expenditures'
				) ,
				'date_to' => 'date',
			) ,
			'add_title' => 'Add an Expenditure',
			'add' => array(
				'date' => 'date',
				'description' => 'text',
				'type' => array(
					'service',
					'goods'
				) ,
				'amount' => 'text',
				'vatrate' => 'text',
				'eu' => 'checkbox',
				'gateway_id' => 'text',
				'resale' => 'checkbox',
			) ,
		));

		$where = '';
		$where_values = array();
		if (isset($_POST['search'])) {
			if (!empty($_POST['description'])) {
				$where.= '`description` LIKE ? AND ';
				$where_values[] = '%' . $_POST['description'] . '%';
			}
			if (!empty($_POST['amount'])) {
				$where.= '`amount` = ? AND ';
				$where_values[] = $_POST['amount'];
			}
			if (!empty($_POST['gateway_id'])) {
				$where.= '`transid` = ? AND ';
				$where_values[] = $_POST['gateway_id'];
			}
			if ($_POST['show'] == 'payments') {
				$where.= '`amount` > 0 AND ';
			}
			if ($_POST['show'] == 'expenditures') {
				$where.= '`amount` < 0 AND ';
			}
			if (!empty($_POST['date_from'])) {
				$date = date_create_from_format('Y-m-d', $_POST['date_from']);
				$date->setTime(0, 0, 0);
				$date_start = $date->getTimestamp();
				$where.= '`date` > ? AND ';
				$where_values[] = $date_start;
			}
			if (!empty($_POST['date_to'])) {
				$date = date_create_from_format('Y-m-d', $_POST['date_to']);
				$date->setTime(0, 0, 0);
				$date_end = ($date->getTimestamp() + 86399);
				$where.= '`date` < ? AND ';
				$where_values[] = $date_end;
			}
		}
		$where = substr($where, 0, -4);
		$func_array_select1 = array();
		$func_array_select1[] = '`transactions`' . (empty($where) ? '' : ' WHERE ' . $where);
		foreach ($where_values as $v) {
			$func_array_select1[] = $v;
		}
		$func_array_select2 = $func_array_select1;
		$func_array_select1[0] = 'SELECT COUNT(*) FROM ' . $func_array_select1[0];
		$total = call_user_func_array(array(
			$db,
			'q'
		) , $func_array_select1);
		$total = $total[0]['COUNT(*)'];
		$pagination = $billic->pagination(array(
			'total' => $total,
		));
		echo $pagination['menu'];
		$func_array_select2[0] = 'SELECT * FROM ' . $func_array_select2[0] . ' ORDER BY `date` DESC LIMIT ' . $pagination['start'] . ',' . $pagination['limit'];
		$transactions = call_user_func_array(array(
			$db,
			'q'
		) , $func_array_select2);
		$billic->set_title('Admin/Transactions');
		echo '<h1><i class="icon-money-banknote"></i> Transactions</h1>';
		$billic->show_errors();
		echo $billic->modules['ListManager']->search_box();
		echo $billic->modules['ListManager']->add_box();
		echo '<div style="float: right;padding-right: 40px;">Showing ' . $pagination['start_text'] . ' to ' . $pagination['end_text'] . ' of ' . $total . ' Transactions</div>' . $billic->modules['ListManager']->search_link() . $billic->modules['ListManager']->add_link();
		echo '<form method="POST">With Selected: <input type="hidden" name="search_post" value="' . base64_encode(json_encode($_POST)) . '"> <button type="submit" class="btn btn-xs btn-primary" name="edit"><i class="icon-edit-write"></i> Edit</button> <button type="submit" class="btn btn-xs btn-danger" name="delete" onclick="return confirm(\'Are you sure you want to delete the selected transactions?\');"><i class="icon-remove"></i> Delete</button><br><table class="table table-striped"><tr><th><input type="checkbox" onclick="checkAll(this, \'ids\')"></th><th>User</th><th>Invoice</th><th>Date</th><th>Description</th><th>Type</th><th>Amount</th><th>VAT</th><th>Intra-EU</th><th>Gateway</th><th>Gateway ID</th><th>Resale</th></tr>';
		if (empty($transactions)) {
			echo '<tr><td colspan="20">No transactions matching filter.</td></tr>';
		}
		foreach ($transactions as $transaction) {
			if ($transaction['amount'] >= 0) {
				$amount = '<font color="green">' . get_config('billic_currency_prefix') . $transaction['amount'] . get_config('billic_currency_suffix') . '</font>';
			} else {
				$amount = '<font color="red">' . get_config('billic_currency_prefix') . $transaction['amount'] . get_config('billic_currency_suffix') . '</font>';
			}
			if ($transaction['description'] == 'Invoice Payment') {
				$transaction['description'] = '';
				$invoiceitems = $db->q('SELECT `description` FROM `invoiceitems` WHERE `invoiceid` = ?', $transaction['invoiceid']);
				foreach ($invoiceitems as $invoiceitem) {
					$transaction['description'].= $invoiceitem['description'] . '<br>';
				}
				$transaction['description'] = substr($transaction['description'], 0, -4);
				$transaction['description'] = preg_replace('~Service #([0-9]+) ~', '<a href="/Admin/Services/ID/$1/">$0</a>', $transaction['description']);
			}
			echo '<tr><td><input type="checkbox" name="ids[' . $transaction['id'] . ']"></td><td><a href="/Admin/Users/ID/' . $transaction['userid'] . '/">' . $transaction['userid'] . '</a></td><td><a href="/Admin/Invoices/ID/' . $transaction['invoiceid'] . '/">' . $transaction['invoiceid'] . '</a></td><td>' . $billic->date_display($transaction['date']) . '</td><td>' . $transaction['description'] . '</td><td>' . ucwords($transaction['type']) . '</td><td>' . $amount . '</td><td>';
			if (is_numeric($transaction['vatrate'])) {
				echo round($transaction['vatrate'], 2) . '%';
			} else {
				echo $transaction['vatrate'];
			}
			echo '</td><td>' . ($transaction['eu'] == 1 ? '<i class="icon-check-mark"></i>' : '<i class="icon-remove"></i>') . '</td><td>' . $transaction['gateway'] . '</td><td>' . $transaction['transid'] . '</td><td>' . ($transaction['resale'] == 1 ? '<i class="icon-check-mark"></i>' : '<i class="icon-remove"></i>') . '</td></tr>';
		}
		echo '</table></form>';
	}
	function exportdata_submodule() {
		global $billic, $db;
		if (empty($_POST['date_start']) || empty($_POST['date_end'])) {
			echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/css/bootstrap-datepicker.min.css">';
			echo '<script>addLoadEvent(function() { $.getScript( "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/js/bootstrap-datepicker.min.js", function( data, textStatus, jqxhr ) { $( "#date_start" ).datepicker({ format: "yyyy-mm-dd" }); $( "#date_end" ).datepicker({ format: "yyyy-mm-dd" }); }); });</script>';
			echo '<form method="POST">';
			echo '<table class="table table-striped" style="width: 300px;"><tr><th colspan="2">Select date range</th></tr>';
			echo '<tr><td>From</td><td><input type="text" class="form-control" name="date_start" id="date_start" value="' . date('Y') . '-01-01"></td></tr>';
			echo '<tr><td>To</td><td><input type="text" class="form-control" name="date_end" id="date_end" value="' . date('Y') . '-12-' . date('t', mktime(0, 0, 0, 12, 1, date('Y'))) . '"></td></tr>';
			echo '<tr><td colspan="2"><input type="checkbox" name="only_expenditures" value="1"> Only export expenditures</td></tr>';
			echo '<tr><td colspan="2" align="right"><input type="submit" class="btn btn-default" name="generate" value="Generate &raquo"></td></tr>';
			echo '</table>';
			echo '</form>';
			return;
		}
		$date = date_create_from_format('Y-m-d', $_POST['date_start']);
		$date->setTime(0, 0, 0);
		$date_start = $date->getTimestamp();
		$date = date_create_from_format('Y-m-d', $_POST['date_end']);
		$date->setTime(0, 0, 0);
		$date_end = ($date->getTimestamp() + 86399);
		ob_end_clean();
		ob_start();
		echo "Date,Description,Type,Amount,VAT Rate,Intra-EU,Gateway,Transaction ID,Resale\r\n";
		$extraSQL = '';
		if ($_POST['only_expenditures'] == 1) $extraSQL.= ' AND `amount` < 0';
		$transactions = $db->q('SELECT `date`, `description`, `type`, `amount`, `vatrate`, `eu`, `gateway`, `transid`, `resale` FROM `transactions` WHERE `date` >= ? AND `date` <= ?' . $extraSQL . ' ORDER BY `date` ASC', $date_start, $date_end);
		foreach ($transactions as $transaction) {
			$transaction['date'] = date('Y-m-d', $transaction['date']);
			if ($_POST['only_expenditures'] == 1) $transaction['amount'] = abs($transaction['amount']);
			if ($transaction['eu'] == 1) $transaction['eu'] = 'Yes';
			else $transaction['eu'] = 'No';
			if ($transaction['resale'] == 1) $transaction['resale'] = 'Yes';
			else $transaction['resale'] = 'No';
			echo str_replace("\n", '', str_replace("\r", '', implode(',', $transaction))) . "\r\n";
		}
		define('DISABLE_FOOTER', true);
		$output = ob_get_contents();
		ob_end_clean();
		header('Content-Disposition: attachment; filename=exported-' . strtolower($_GET['Module']) . '-' . time() . '.csv');
		header('Content-Type: application/force-download');
		header('Content-Type: application/octet-stream');
		header('Content-Type: application/download');
		header('Content-Length: ' . strlen($output));
		echo $output;
		exit;
	}
}
