"C:\Program Files\Git\bin\sh.exe" --login -i -c "git archive -o patch.zip HEAD $(git diff --name-only HEAD^..HEAD)"

pause