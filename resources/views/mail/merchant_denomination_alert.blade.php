<body>
	<p>{{$data->merchant->name}} is set to a low alarm limit of {{$data->code_percentage}}%. Currently the amount in inventory is {{$data->code_count}}</p>
    <p>Inventory low for SKU: {{number_format ( $data->redemption_value, 2 )}}</p>
    <p>Please update the inventory with the appropriate inventory supply.</p>
</body>