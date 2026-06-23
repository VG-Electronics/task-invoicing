# Implementation Notes

This is a test recruitment task. I am not a DDD expert, so I wanted and tried my best to learn the concepts of this architecture in practice.

Claude Code was used to speed up the development - I was guiding it and reviewing the changes step by step. 
I made every architecture decision and I wrote big part of the code manually.

This task took me ~3,5 hours together with learning a bit about DDD, making decisions and testing the code.

Please import **postman.postman_collection.json** to Postman to test the API.

## Layers

### Business entities & Database models
Invoice and InvoiceProductLine entities are separated from the database models. InvoiceRepository interface specifies the methods needed by the domain layer.
This way the changes in the database layer don't have any impact on the domain logic, e.g. database might be changed or Eloquent can be replaced with another ORM and domain layer won't notifce it.
InvoiceMapper is a bridge which translates database models to business entities and vice versa.

### Commands
Actions are in the Application layer. The command-pattern was not used in this simple project to not overcomplicate the code.

### DTOs
Data Transfer Objects are used to pack a set of data and return it as a ready to use object with all calculations etc.

### API
The API layer is responsible for handling HTTP requests. It validates the requests and executes the commands or queries.
However, validation is a business logic and probably should be moved to the domain layer.

## Exceptions
Domain exceptions are stored in the Domain layer as a separate classes.

## TODO in production code:
- Retry and error status of the invoice
- Logging the events and errors
- Authentication

# Requirements

## Invoice Structure:

The invoice should contain the following fields:
* **Invoice ID**: Auto-generated during creation.
* **Invoice Status**: Possible states include `draft,` `sending,` and `sent-to-client`.
* **Customer Name** 
* **Customer Email** 
* **Invoice Product Lines**, each with:
  * **Product Name**
  * **Quantity**: Integer, must be positive. 
  * **Unit Price**: Integer, must be positive.
  * **Total Unit Price**: Calculated as Quantity x Unit Price. 
* **Total Price**: Sum of all Total Unit Prices.

## Required Endpoints:

1. **View Invoice**: Retrieve invoice data in the format above.
2. **Create Invoice**: Initialize a new invoice.
3. **Send Invoice**: Handle the sending of an invoice.

## Functional Requirements:

### Invoice Criteria:

* An invoice can only be created in `draft` status. 
* An invoice can be created with empty product lines. 
* An invoice can only be sent if it is in `draft` status. 
* An invoice can only be marked as `sent-to-client` if its current status is `sending`. 
* To be sent, an invoice must contain product lines with both quantity and unit price as positive integers greater than **zero**.

### Invoice Sending Workflow:

* **Send an email notification** to the customer using the `NotificationFacade`. 
  * The email's subject and message may be hardcoded or customized as needed. 
  * Change the **Invoice Status** to `sending` after sending the notification.

### Delivery:

* Upon successful delivery by the Dummy notification provider:
  * The **Notification Module** triggers a `ResourceDeliveredEvent` via webhook.
  * The **Invoice Module** listens for and captures this event.
  * The **Invoice Status** is updated from `sending` to `sent-to-client`.
  * **Note**: This transition requires that the invoice is currently in the `sending` status.

## Technical Requirements:

* **Preferred Approach**: Domain-Driven Design (DDD) is preferred for this project. If you have experience with DDD, please feel free to apply this methodology. However, if you are more comfortable with another approach, you may choose an alternative structure.
* **Alternative Submission**: If you have a different, comparable project or task that showcases your skills, you may submit that instead of creating this task.
* **Unit Tests**: Core invoice logic should be unit tested. Testing the returned values from endpoints is not required.
* **Documentation**: Candidates are encouraged to document their decisions and reasoning in comments or a README file, explaining why specific implementations or structures were chosen.

## Note on the Notification Module:

The Notification module included in this repository is a minimal, mock integration example. It is intentionally simple and should not be treated as a reference for DDD structure or for the expected invoice design.

## Setup Instructions:

* Start the project by running `./start.sh`.
* To access the container environment, use: `docker compose exec app bash`.
