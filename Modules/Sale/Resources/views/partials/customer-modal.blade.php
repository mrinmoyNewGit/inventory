<div class="modal fade" id="customerCreateModal" tabindex="-1" role="dialog" aria-labelledby="customerCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document"> <!-- wider modal -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerCreateModalLabel">Add Customer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @include('utils.alerts')

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="customer_name">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="customer_name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="customer_email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="customer_email">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="customer_phone">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="customer_phone" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="city">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="city" >
                        </div>
                        <div class="form-group col-md-4">
                            <label for="country">Country <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="country">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="address" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        Create Customer <i class="bi bi-check"></i>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
