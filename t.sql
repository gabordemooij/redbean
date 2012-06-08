create or replace trigger book_insert
before insert on book
for each row
begin
    select book_id_seq.nextval into :new.id from dual;
end;
/
