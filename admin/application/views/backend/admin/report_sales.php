<script>
var chart = AmCharts.makeChart("chartdiv",{
	"type"			: "pie",
	"theme"			: "dark",
	"titleField"	: "project",
	"valueField"	: "amount",
	"colorField"	: "color",
	"innerRadius"	: "40%",
	"angle"			: "15",
	"depth3D"		: 10,
	"pathToImages"	: "<?php echo base_url();?>assets/backend/plugins/amcharts/images/",
	"amExport": {
					"top": 0,
                    "right": 0,
                    "buttonColor": '#EFEFEF',
                    "buttonRollOverColor":'#DDDDDD',
					"imageFileName"	: "income-expense",
                    "exportPNG":true,
                    "exportJPG":true,
                    "exportPDF":true,
                    "exportSVG":true
	},
	"dataProvider"	: [
		<?php
		$income		=	0;
		$expense	=	0;
		$this->db->select('*,customer.name AS customer_name,
                   product.name AS product_name,
                   selling_type.name AS selling_type_name,
                   employee.name AS seller_name');
		$this->db->order_by('timestamp' , 'desc');
		$this->db->where('timestamp >=' , $timestamp_start);
		$this->db->where('timestamp <=' , $timestamp_end);
		if (isset($seller_id) && !empty($seller_id))
			$this->db->where('seller_id =' , $seller_id);
		if (isset($customer_id) && !empty($customer_id))
			$this->db->where('invoice_details.customer_id =' , $customer_id);
		if (isset($selling_type_id) && !empty($selling_type_id))
			$this->db->where('selling_type_id =' , $selling_type_id);

		$this->db->join('customer','invoice_details.customer_id = customer.customer_id','left');
		$this->db->join('employee','invoice_details.seller_id = employee.employee_id','left');
		$this->db->join('product','invoice_details.product_id = product.product_id','left');
		$this->db->join('selling_type','invoice_details.selling_type_id = selling_type.id','left');
		$sales	=	$this->db->get('invoice_details')->result_array();
		$sellers_arr = array();
		foreach ($sales as $index => $row)
		{
       		$seller_id = $row['seller_id'];
			if (array_key_exists($row['seller_id'], $sellers_arr)) {
				$sellers_arr[$seller_id]['commission'] += $row['commission'];
			}else{
				$sellers_arr[$seller_id]['commission'] = $row['commission'];
				$sellers_arr[$seller_id]['name'] = $row['seller_name'];

			}
		}
//var_dump($sellers_arr);
		foreach($sellers_arr as $seller_id => $seller ):

		?>
		{
			"project": "<?php echo $seller['name']; ?>",
			"amount": <?php echo $seller['commission']; ?>,
			"color" : "#1AACAB"
		},
		<?php
		endforeach;
		?>
	]
});
</script>

<!-- begin #content -->
<div id="content" class="content">

	<!-- begin breadcrumb -->
	<ol class="breadcrumb pull-right">
		<li>
			<a href="<?php echo base_url();?>index.php?admin/dashboard">
				<?php echo translate('dashboard');?>
			</a>
		</li>
		<li>
			<a href="<?php echo base_url();?>index.php?admin/report/payment">
				<?php echo translate('payments');?>
			</a>
		</li>
		<li>
			<a href="<?php echo base_url();?>index.php?admin/report/customer">
				<?php echo translate('customer_payments');?>
			</a>
		</li>
	</ol>
	<!-- end breadcrumb -->

	<!-- begin page-header -->
	<h1 class="page-header"><?php echo $page_title;?></h1>
	<!-- end page-header -->
	<br>

	<div class="row">
	<!-- 	<div class="col-md-3"></div>
	 -->	<div class="col-md-9">
			<!-- begin panel -->
	        <div class="panel panel-inverse" data-sortable-id="ui-widget-1">
                <div class="panel-heading">
                    <div class="panel-heading-btn">
                        <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-default" data-click="panel-expand"><i class="fa fa-expand"></i></a>
                        <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-warning" data-click="panel-collapse"><i class="fa fa-minus"></i></a>
                    </div>
                    <h4 class="panel-title">
                    	<?php echo translate('filter-by');?>
                    </h4>
                </div>
                <div class="panel-body">
                    <?php 
					    echo form_open(base_url() . 'index.php?admin/report/' . $report_type , array(
					        'class' => 'form-horizontal form-bordered' , 'data-parsley-validate' => 'true' , 'enctype' => 'multipart/form-data'
					    ));
					?>

						<div class="form-group">
						    <label class="col-md-3"><?php echo translate('date_range');?></label>
						    <div class="col-md-8">
						        <div class="input-group input-daterange">
						            <input type="text" class="form-control" name="start"
						            	value="<?php echo date('m/d/Y' , $timestamp_start);?>" />
						            <span class="input-group-addon"><?php echo translate('to');?></span>
						            <input type="text" class="form-control" name="end"
						            	value="<?php echo date('m/d/Y' , $timestamp_end);?>" />
						        </div>
						    </div>
						</div>

						<div class="form-group">
						    <label class="col-md-3"><?php echo translate('seller');?></label>
						    <div class="col-md-8">
						        <div class="input-group input-daterange">
						             <select class="form-control selectpicker" data-size="10" data-live-search="true" data-style="btn-white" 
							    	data-parsley-required="false" name="seller_id"
							    		onchange="return get_employee(this.value)">
					                <option value="" ><?php echo translate('select_seller');?></option>
					                <?php 
					                	$employees	=	$this->db->get('employee')->result_array();
					                	foreach ($employees as $row):
					                ?>
					                	<option value="<?php echo $row['employee_id'];?>" <?php echo (isset($seller_id) && ($seller_id == $row['employee_id']))?'selected':''; ?> >
					                		<?php echo $row['name'];?> - [ <?php echo $row['employee_id'];?> ]
					                	</option>
					            	<?php endforeach;?>
					            </select>
						        </div>
						    </div>
						</div>


						<div class="form-group">
						    <label class="col-md-3"><?php echo translate('customer');?></label>
						    <div class="col-md-8">
						        <div class="input-group input-daterange">
						        <select class="form-control selectpicker" data-size="10" data-live-search="true" data-style="btn-white" 
							    	data-parsley-required="false" name="customer_id">
					                <option value="" ><?php echo translate('select_customer');?></option>
					                <?php 
					                	$customers	=	$this->db->get('customer')->result_array();
					                	foreach ($customers as $row):
					                ?>
					                	<option value="<?php echo $row['customer_id'];?>"  <?php echo (isset($customer_id) && ($customer_id == $row['customer_id']))?'selected':''; ?> >
					                		<?php echo $row['name'];?> - [ <?php echo $row['customer_code'];?> ]
					                	</option>
					            	<?php endforeach;?>
					            </select>
						        </div>
						    </div>
						</div>


						<div class="form-group">
						    <label class="col-md-3"><?php echo translate('selling-type');?></label>
						    <div class="col-md-8">
						        <div class="input-group input-daterange">
						        <select class="form-control selectpicker" data-size="10" data-live-search="true" data-style="btn-white" 
							    	data-parsley-required="false" name="selling_type_id">
					                <option value="" ><?php echo translate('select-type');?></option>
					                <?php 
					                	$types	=	$this->db->get('selling_type')->result_array();
   	 				                	foreach ($types as $row):
					                ?>
					                	<option value="<?php echo $row['id'];?>"  <?php echo (isset($selling_type_id) && ($selling_type_id == $row['id']))?'selected':''; ?> >
					                		<?php echo $row['name'].' - '.$row['percentage'].'%'; ?>
					                	</option>
					            	<?php endforeach;?>
					            </select>
						        </div>
						    </div>
						</div>









		
							


						<div class="form-group">
							<label class="col-md-3 col-sm-3"></label>
							<div class="col-md-8 col-sm-8">
								<button type="submit" class="btn btn-success"><?php echo translate('go');?></button>
							</div>
						</div>

					<?php echo form_close();?>
                </div>
            </div>
	        <!-- end panel -->
		</div>
		<div class="col-md-3">
			<div class="alert alert-info fade in m-b-15">
				<strong>
					<?php echo date("d M, Y" , $timestamp_start) . " - " . date("d M, Y" , $timestamp_end);?>
				</strong>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
	        <div class="widget-chart bg-black">
	            <div class="">
	                <h4 class="chart-title">
	                    
	                </h4>
	                <div id="chartdiv" style="height: 500px; width: 100%;"></div>
	            </div>
	        </div>
	    </div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<!-- begin panel -->
            <div class="panel panel-inverse">
                <div class="panel-heading">
                    <div class="panel-heading-btn">
                        <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-default" data-click="panel-expand"><i class="fa fa-expand"></i></a>
                        <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-warning" data-click="panel-collapse"><i class="fa fa-minus"></i></a>
                    </div>
                    <h4 class="panel-title"><?php echo translate('Sales');?></h4>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        
                        <table id="data-table" class="table table-striped table-bordered nowrap display" width="100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo translate('seller');?></th>
                                    <th><?php echo translate('customer');?></th>
                                    <th><?php echo translate('item');?></th>
                                    <th><?php echo translate('invoice');?></th>
                                    <th><?php echo translate('selling-type');?></th>
                                    <th><?php echo translate('commission');?></th>
                                    <th><?php echo translate('date');?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            	$count	=	1;
                            	foreach ($sales as $row):
                            ?>
                                <tr>
                                    <td><?php echo $count++;?></td>
                                    <td><?php echo $row['seller_name']; ?></td>
                                    <td><?php echo $row['customer_name'];?></td>
                                    <td><?php echo $row['product_name'];?></td>
                                    <td><?php echo $row['invoice_code'];?></td>
                                    <td><?php echo $row['selling_type_name'];?></td>
                                    <td><?php echo $row['commission'];?></td>
                                    <td><?php echo $row['timestamp']?date("d F, Y H:i:s" , $row['timestamp']+3660):'';?></td>
                                </tr>
                            <?php endforeach;?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- end panel -->
		</div>
	</div>

</div>

<script type="text/javascript">


    function get_employee(employee_id) {

    	$.ajax({
            url: '<?php echo base_url();?>index.php?admin/get_employee/' + employee_id ,
            success: function(response)
            {
                jQuery('#sold_by_holder').html(response);
            }
        });

    }



    // function get_customer_discount(customer_id) {

    // 	$.ajax({
    //         url: '<?php echo base_url();?>index.php?admin/get_customer_discount_percentage/' + customer_id ,
    //         success: function(response)
    //         {
    //             jQuery('#customer_discount_holder').html(response);
    //         }
    //     });
           
    // }
</script>
























