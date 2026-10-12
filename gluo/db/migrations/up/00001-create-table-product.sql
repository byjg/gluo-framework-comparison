create table product
(
    id int auto_increment not null primary key,
    name varchar(100) not null,
    price decimal(10,2) not null,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

insert into product (name, price) values
    ('Keyboard', 49.90),
    ('Mouse', 19.90),
    ('Monitor', 899.00),
    ('Headset', 129.50),
    ('Webcam', 75.00);
