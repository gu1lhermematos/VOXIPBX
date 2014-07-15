<div class="fullWidth">
	<div class="innerWidth940">
		<h2>CDR</h2>
       		 <br />
      	</div><!--innerWidth940-->
	<iframe id="cdrIframe" src="http://pabx.xturbo.com.br/cdr/cdr.php" style="width:100%; border:0;"></iframe> 
</div><!--fullWidth-->

<script>
	$(document).ready(function(e){
		$("#cdrIframe").css('height',(parseInt(window.innerHeight) - 200));
	});
</script>
