<!DOCTYPE html>
<html>
<head>

	<title>QualityChecker</title>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

	<!-- Latest compiled JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>


	<link rel="stylesheet" type="text/css" href="style.css">

</head>
<body>

	<div class='buttons'>
			<form action="qc.php" method='post' enctype="multipart/form-data">
			  <input type="file" class='btn btn-default btn-lg' name="file" accept=".xlsx" required>
			  <input type="submit" class='btn btn-info btn-lg button2' name="submit">
			</form>
	</div>

	<div class="container">
		<div class="content">
			<h2>Quality Check</h2>
			<div class="dwnld">
				<button class="btn btn-info btn-sm">Download</button>
			</div>
			
			<table id="table" class="table table-striped table-bordered"">
				<tr>
					<th>ASIN</th>
					<th>Title</th>
					<th>Description</th>
					<th>Bullet 1</th>
					<th>Bullet 2</th>
					<th>Bullet 3</th>
					<th>Bullet 4</th>
					<th>Bullet 5</th>
				</tr>

			</table>
		</div>
	</div>
</body>
</html>