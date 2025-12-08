<?php

return [
    // Document types
    'commercial_invoice' => '商业发票',
    'packing_list' => '装箱单',
    'purchase_order' => '采购订单',
    'proforma_invoice' => '形式发票',
    
    // Document headers
    'invoice_no' => '发票号',
    'date' => '日期',
    'po_number' => '采购单号',
    'shipment_info' => '货运信息',
    
    // Parties
    'exporter' => '出口商',
    'importer' => '进口商',
    'consignee' => '收货人',
    'notify_party' => '通知方',
    'shipper' => '发货人',
    
    // Shipping details
    'shipping_details' => '运输详情',
    'port_of_loading' => '装货港',
    'port_of_discharge' => '卸货港',
    'final_destination' => '最终目的地',
    'vessel' => '船名',
    'voyage' => '航次',
    'bl_number' => '提单号',
    'container_no' => '集装箱号',
    
    // Table headers
    'no' => '序号',
    'description' => '产品描述',
    'customer_code' => '客户代码',
    'supplier_code' => '供应商代码',
    'hs_code' => 'HS编码',
    'origin' => '原产地',
    'qty' => '数量',
    'qty_carton' => '每箱数量',
    'cartons' => '箱数',
    'unit_price' => '单价',
    'amount' => '金额',
    'nw_unit' => '单位净重 (kg)',
    'gw_unit' => '单位毛重 (kg)',
    'total_nw' => '总净重 (kg)',
    'total_gw' => '总毛重 (kg)',
    'cbm' => '立方米',
    
    // Totals
    'total' => '总计',
    'subtotal' => '小计',
    'discount' => '折扣',
    'customs_discount' => '海关折扣',
    'grand_total' => '总金额',
    
    // Payment
    'payment_terms' => '付款条款',
    'bank_information' => '银行信息',
    'bank_name' => '银行名称',
    'account_name' => '账户名',
    'account_number' => '账号',
    'swift_code' => 'SWIFT代码',
    'bank_address' => '银行地址',
    
    // Additional
    'notes' => '备注',
    'remarks' => '说明',
    'signature' => '签名',
    'authorized_signature' => '授权签名',
    'company_stamp' => '公司印章',
    
    // Display options
    'display_options' => '显示选项',
    'show_payment_terms' => '显示付款条款',
    'show_bank_information' => '显示银行信息',
    'show_exporter_details' => '显示出口商详情',
    'show_importer_details' => '显示进口商详情',
    'show_shipping_details' => '显示运输详情',
    'show_supplier_code' => '显示供应商代码',
    'show_customer_code' => '显示客户代码',
    'show_hs_codes' => '显示HS编码',
    'show_country_of_origin' => '显示原产国',
    'show_weight_volume' => '显示重量和体积',
    
    // Units
    'pcs' => '件',
    'kg' => '公斤',
    'm3' => '立方米',
    'usd' => '美元',
];
