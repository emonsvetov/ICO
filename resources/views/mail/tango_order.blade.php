<body>
        Attention: Action Required {{ $data->order_id }}<br /><br />
		An order has been placed for items that require shipping<br/>
        User Information:<br/>
        <em style='font-weight:bold'>Name:</em> {{ $data->user_info->first_name }} {{ $data->user_info->last_name }}<br/>
		<em style='font-weight:bold'>Email:</em> {{ $data->user_info->email }}<br/><br/>
        Shipping Address Information:<br/><br/>
        <em style='font-weight:bold'>ship_to_name</em> {{ $data->order_address->ship_to_name }}<br/>
        <em style='font-weight:bold'>line_1</em> {{ $data->order_address->line_1 }}<br/>
        <em style='font-weight:bold'>line_2</em> {{ $data->order_address->line_2 }}<br/>
        <em style='font-weight:bold'>city</em> {{ $data->order_address->city }}<br/>
        <em style='font-weight:bold'>zip</em> {{ $data->order_address->zip }}<br/>
		<em style='font-weight:bold'>state:</em> {{ $data->ship_to_state->name }}<br/>
        <em style='font-weight:bold'>country:</em> {{ $data->ship_to_country->name }}
</body>