
       
		<div id="firstGraph"></div>
        <div id="secondGraph"></div>
        
       <script type="text/javascript">
			$(function () {
				$('#firstGraph').highcharts({
					chart: {
						zoomType: 'x',
						spacingRight: 20
					},
					title: {
						text: 'Ligações dos Últimos 30 Dias'
					},
					subtitle: {
						text: document.ontouchstart === undefined ?
							'Click e arraste para dar zoom' :
							'Pinch para dar zoom'
					},
					xAxis: {
						type: 'datetime',
						maxZoom: 14 * 24 * 3600000, 
						title: {
							text: null
						}
					},
					yAxis: {
						title: {
							text: 'Total'
						}
					},
					tooltip: {
						shared: true
					},
					legend: {
						enabled: false
					},
					plotOptions: {
						area: {
							fillColor: {
								linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1},
								stops: [
									[0, Highcharts.getOptions().colors[0]],
									[1, Highcharts.Color(Highcharts.getOptions().colors[1]).setOpacity(8).get('rgba')]
								]
							},
							lineWidth: 1,
							marker: {
								enabled: false
							},
							shadow: false,
							states: {
								hover: {
									lineWidth: 1
								}
							},
							threshold: null
						}
					},
			
					series: [{
						type: 'area',
						name: 'Total',
						pointInterval: 24 * 3600 * 1000,
						pointStart: Date.UTC(<?php echo "20" . $graphStartOn[2]?>, <?php echo $graphStartOn[1]-1?>, <?php echo $graphStartOn[0]?>),
						data: [
							<?php
								$string = "";
								foreach($allCalls as $data){
									$string .= $data['data_to_show'] . ",";
								}
								echo substr($string,0,-1);
							?>
						]
					}]
				});
			});
    

			$(function () {
					
					// Radialize the colors
					Highcharts.getOptions().colors = Highcharts.map(Highcharts.getOptions().colors, function(color) {
						return {
							radialGradient: { cx: 0.5, cy: 0.3, r: 0.7 },
							stops: [
								[0, color],
								[1, Highcharts.Color(color).brighten(-0.3).get('rgb')] // darken
							]
						};
					});
					
					// Build the chart
					$('#secondGraph').highcharts({
						chart: {
							plotBackgroundColor: null,
							plotBorderWidth: null,
							plotShadow: false
						},
						title: {
							text: 'Média operadoras utilizadas últimos 30 dias'
						},
						tooltip: {
							pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
						},
						plotOptions: {
							pie: {
								allowPointSelect: true,
								cursor: 'pointer',
								dataLabels: {
									enabled: true,
									color: '#14b591',
									connectorColor: '#14b591',
									formatter: function() {
										return '<b>'+ this.point.name +'</b>: '+ this.percentage +' %';
									}
								}
							}
						},
						series: [{
							type: 'pie',
							name: 'Utilização',
							data: [
									<?php
										$string = "";
										foreach($callOperators as $operator){
											$string .="[";
											$string .= "'" . $operator['operadora'] . "',";
											$string .= $operator['total'];
											$string .= "],";
										}
										echo substr($string,0,-1);
									?>
							
							]
						}]
					});
				});
    

		</script>
        
        <div class="clear"></div>
    </div><!--innerWidth940-->
</div><!--fullWidth-->
