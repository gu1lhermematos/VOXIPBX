<div class="fullWidth">
    <div class="innerWidth940">
    	<h2>Gráficos</h2>
        <br />
        <?php echo $this->Form->create('generate_custom_graph',array('url'=>array('controller'=>'Dashboard','action'=>'gen_custom_graph'))); ?>
            <div class="col-lg-6">
                <div class="input-group">
                    <span class="input-group-btn"><?php echo $this->form->button('Começa em',array('class'=>'btn btn-default'))?></span>
                	<?php echo $this->Form->input('firstDate',array('label'=>false,'div'=>false,'class'=>'form-control','readonly'=>'readonly')) ?>
                </div><!--input-group -->
            </div><!--col-lg-6 -->
            
            <div class="col-lg-6">
                <div class="input-group">
                    <span class="input-group-btn"><?php echo $this->form->button('Até',array('class'=>'btn btn-default'))?></span>
                	<?php echo $this->Form->input('secondDate',array('label'=>false,'div'=>false,'class'=>'form-control','readonly'=>'readonly')) ?>
                </div><!--input-group -->
            </div><!--col-lg-6 -->
            
            <div class="clear"></div>
        	<br />
            
            <h3>Filtar operadoras</h3>
            <br />
            <?php
				$i=0; //break line counter
				$k=0; //checkbox indefier
				foreach($operators as $op){
					?>
                    	 <div class="col-lg-3">
                            <div class="input-group">
                                <span class="input-group-addon"><input class="operatorFilter" type="checkbox" value="<?php echo $op['Controle']['operadora']?>" name="filterFor<?php echo $k ?>" id="filterFor<?php echo $k ?>"></span>
                                <input type="text" class="form-control" readonly="readonly" value="<?php echo $op['Controle']['operadora']?>">
                            </div><!--input-group -->
                        </div><!--col-lg-6 -->
                    <?php 
					if ($i == 3){
						echo "<div class='clear'></div><br />";
						$i=0;
					}
					$i++;
					$k++;
				}
			?>
       	
		  	<div class='clear'></div><br />
		<?php echo $this->Form->button('Selecionar Todas',array('type'=>'button','class'=>'btn btn-default','style'=>'margin-left:15px;','role'=>'0','id'=>'buttonMarkAll')) ?>
        <br />
        <br />
		<?php echo $this->Form->input('operatorFilterGoesTo',array('type'=>'hidden','value'=>$k)); ?>
        <?php echo $this->Form->button('Gerar',array('class'=>'btn btn-success','type'=>'submit','style'=>'margin-left:15px;'))?>
        <?php echo $this->Form->end(); ?>
	</div><!--innerWidth940-->
</div>

<script>
	$('#generate_custom_graphFirstDate, #generate_custom_graphSecondDate').datepicker({
		format: 'dd-mm-yyyy'
	})
	
	$("#generate_custom_graphGraphsForm").submit(function(e){
		//verify the checkboxes
		var goOn = false;
		$(".operatorFilter").each(function(index, element) {
			if (element.checked == true){
				goOn = true;
			}
        });
		
		var date1 = $("#generate_custom_graphFirstDate").val();
		var date2 = $("#generate_custom_graphSecondDate").val();
		if (date1 == ""){
			alert("Preenche a data inicial.");
			return false;
		}
		else if (date2 == ""){
			alert("Preencha a data final.");
			return false;
		}
		else if(goOn == false){
			alert("Voce deve selecionar ao menos uma operadora");
			return false;
		}
		else{
			date1 = date1.split("-");
			date2 = date2.split("-");
			
			var date1Obj = new Date();
			var date2Obj = new Date();
			date1Obj = date1Obj.setFullYear(date1[2],date1[1],date1[0]);
			date2Obj = date2Obj.setFullYear(date2[2],date2[1],date2[0]);
			if (date1Obj > date2Obj){
				alert("Data inicial deve ser menor que a data final.");
				return false;
			}
			else{
				return true;
			}
		}
	});
	
	$("#buttonMarkAll").on('click',function(e){
		
		$(".operatorFilter").each(function(index, element) {
           
        	if ($("#buttonMarkAll").attr('role') == "0"){
				element.checked = true;
				$("#buttonMarkAll").html("Desmarcar todos");
			}
			else{
				element.checked = false;
				$("#buttonMarkAll").html("Marcar todos");
			}
		});
		
		if ($("#buttonMarkAll").attr('role') == "0"){
			$("#buttonMarkAll").attr('role','1');
		}
		else{
			$("#buttonMarkAll").attr('role','0');
		}
		
	});
</script>