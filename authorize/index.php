
<div class="panel">
    <div class="panel-heading">
        <!-- Product Info -->
    </div>
    <div class="panel-body">
		
        <!-- Payment form -->
        <form action="PaymentTransactions/charge-credit-card.php" class="horizontal-form" method="POST">
            <div class="form-group">
                <label>EMAIL</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email" required="">
            </div>
			<br>
            <div class="form-group">
                <label>CARD NUMBER</label>
                <input type="text" name="card_number" placeholder="4111111111111111" autocomplete="off" required="" value="4111111111111111">
            </div>
			<br>
            <div class="row">
                <div class="left">
                    <div class="form-group">
                        <label>EXPIRY DATE</label>
                        <div class="col-1">
                            <input type="text" name="card_exp_month" placeholder="MM" required="" value="12">
                        </div>
                        <div class="col-2">
                            <input type="text" name="card_exp_year" placeholder="YYYY" required="" value="2025">
                        </div>
                    </div>
                </div>
				<br>
                <div class="right">
                    <div class="form-group">
                        <label>CVC CODE</label>
                        <input type="text" name="card_cvc" placeholder="CVC" autocomplete="off" required="" value="123">
						<input type="hidden" name="amount" value="50">
						<p>Amount : 50</p>
                    </div>
                </div>
            </div>
			<br>
            <button type="submit" class="btn btn-success">Submit Payment</button>
        </form>
    </div>
</div>