<div class="fullWidth">
    <div class="innerWidth940">
    	<h2>Gráficos Customizados</h2>
    </div>
		<div id="graph" style="width:100%;"></div>
        <script>
			$(function () {
				$('#graph').highcharts({
					title: {
						text: 'Ligações entre <?php echo $intervalDays[0] ?> e <?php echo $intervalDays[count($intervalDays)-1] ?> ',
						x: -20 //center
					},
					subtitle: {
						text: '',
						x: -20
					},
					xAxis: {
						categories: [
										<?php
											$string = "";
											foreach($intervalDays as $day){
												$day = explode("-",$day);
												$string .= "'" . $day[0] . "/" . $day[1] . "',";
											}
											echo substr($string,0,-1);
										?>
									]
					},
					yAxis: {
						title: {
							text: 'Ligações'
						},
						plotLines: [{
							value: 0,
							width: 1,
							color: '#FFF'
						}]
					},
					tooltip: {
						valueSuffix: ''
					},
					legend: {
						layout: 'vertical',
						align: 'right',
						verticalAlign: 'middle',
						borderWidth: 0
					},
					series: [
						<?php

							$string = "";
							foreach($arrFinal as $operator){
								$dataString = "";
								foreach($operator['operator']['days'] as $day){
									$dataString .= $day . ",";
								}
								$dataString = substr($dataString,0,-1);
								
								$string .= "{";
								$string .= "name:'" . $operator['operator']['name'] . "',";
								$string .= "data:[" . $dataString . "]";
								$string .= "},";
							}
							echo substr($string,0,-1);
						?>
					]
				});
			});
		</script>    
</div>
