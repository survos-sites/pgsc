psql "postgresql://postgres:y5q5n6mX2EBebVb@5.161.107.103:5432/pgsc" \
    -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname='pgsc' AND pid <> pg_backend_pid();"
#    \
#    -c "DROP DATABASE pgsc;" \
#    -c "CREATE DATABASE pgsc;"
